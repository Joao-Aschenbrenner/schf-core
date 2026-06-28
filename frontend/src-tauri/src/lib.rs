use std::io::{BufRead, BufReader};
use std::net::UdpSocket;
use std::process::{Command, Stdio};
use std::sync::atomic::{AtomicBool, Ordering};
use std::sync::Mutex;
use std::thread;
use serde::{Deserialize, Serialize};
use tauri::Manager;

const DISCOVERY_PORT: u16 = 42069;
const DISCOVERY_REQ: &[u8] = b"SCF_DISCOVER";
const DISCOVERY_RESP_PREFIX: &str = "SCF_RESPONSE";

struct DiscoveryState {
    running: AtomicBool,
}

struct TunnelState {
    process: Mutex<Option<std::process::Child>>,
    tunnel_url: Mutex<String>,
    tunnel_type: Mutex<String>,
}

#[derive(Serialize)]
struct TunnelInfo {
    running: bool,
    url: String,
    tunnel_type: String,
}

#[derive(Serialize, Deserialize)]
struct ContainerInfo {
    name: String,
    status: String,
}

#[derive(Serialize)]
struct NetworkInfo {
    hostname: String,
    ips: Vec<String>,
    adapter_name: String,
    is_static: bool,
    gateway: String,
    subnet_mask: String,
}

#[derive(Serialize)]
struct DiscoveryResult {
    hostname: String,
    ip: String,
    port: u16,
}

fn get_local_ip() -> String {
    if let Ok(socket) = UdpSocket::bind("0.0.0.0:0") {
        if socket.connect("8.8.8.8:53").is_ok() {
            if let Ok(addr) = socket.local_addr() {
                return addr.ip().to_string();
            }
        }
    }
    String::from("127.0.0.1")
}

fn parse_netsh_config(output: &str) -> (String, bool, String, String) {
    let mut adapter = String::new();
    let mut is_static = false;
    let mut gateway = String::new();
    let mut subnet = String::new();

    for line in output.lines() {
        let trimmed = line.trim();
        if trimmed.contains("Configura") || trimmed.contains("Configuration") {
            if let Some(name) = trimmed.split("para interface").nth(1) {
                adapter = name.trim().trim_matches('"').to_string();
            } else if let Some(name) = trimmed.split("for interface").nth(1) {
                adapter = name.trim().trim_matches('"').to_string();
            } else if let Some(name) = trimmed.split(':').nth(1) {
                adapter = name.trim().to_string();
            }
        }
        if trimmed.contains("DHCP ativado:") || trimmed.contains("DHCP enabled:") {
            let val = trimmed.split(':').last().unwrap_or("").trim();
            is_static = val.eq_ignore_ascii_case("No") || val.eq_ignore_ascii_case("Nao");
        }
        if trimmed.starts_with("M�scara") || trimmed.starts_with("Subnet") {
            if let Some(mask) = trimmed.split(':').nth(1) {
                subnet = mask.trim().to_string();
            }
        }
        if trimmed.contains("Gateway") && !trimmed.contains("DHCP") {
            if let Some(gw) = trimmed.split(':').nth(1) {
                gateway = gw.trim().to_string();
            }
        }
    }

    (adapter, is_static, gateway, subnet)
}

#[tauri::command]
fn check_docker_installed() -> bool {
    Command::new("docker")
        .arg("--version")
        .output()
        .map(|o| o.status.success())
        .unwrap_or(false)
}

#[tauri::command]
fn check_docker_running() -> bool {
    Command::new("docker")
        .arg("info")
        .output()
        .map(|o| o.status.success())
        .unwrap_or(false)
}

#[tauri::command]
fn get_containers(docker_dir: String) -> Result<Vec<ContainerInfo>, String> {
    let compose_file = format!("{}/docker-compose.yml", docker_dir);
    if !std::path::Path::new(&compose_file).exists() {
        return Ok(vec![]);
    }

    let output = Command::new("docker-compose")
        .args(["-f", &compose_file, "ps", "--format", "json"])
        .output()
        .map_err(|e| format!("Erro: {}", e))?;

    if !output.status.success() {
        let stderr = String::from_utf8_lossy(&output.stderr);
        if stderr.contains("no such") || stderr.contains("can't find") {
            return Ok(vec![]);
        }
        return Err(stderr.to_string());
    }

    let stdout = String::from_utf8_lossy(&output.stdout);
    let containers: Vec<ContainerInfo> = stdout
        .lines()
        .filter(|l| !l.is_empty())
        .filter_map(|line| {
            serde_json::from_str::<serde_json::Value>(line).ok().map(|v| ContainerInfo {
                name: v.get("Name").and_then(|n| n.as_str()).unwrap_or("?").to_string(),
                status: v.get("Status").and_then(|s| s.as_str()).unwrap_or("?").to_string(),
            })
        })
        .collect();

    Ok(containers)
}

#[tauri::command]
fn docker_compose_up(docker_dir: String) -> Result<String, String> {
    let compose_file = format!("{}/docker-compose.yml", docker_dir);
    if !std::path::Path::new(&compose_file).exists() {
        return Err("Arquivo docker-compose.yml n�o encontrado".to_string());
    }

    let output = Command::new("docker-compose")
        .args(["-f", &compose_file, "up", "-d"])
        .output()
        .map_err(|e| format!("Erro: {}", e))?;

    if output.status.success() {
        Ok(String::from_utf8_lossy(&output.stdout).to_string())
    } else {
        Err(String::from_utf8_lossy(&output.stderr).to_string())
    }
}

#[tauri::command]
fn docker_compose_down(docker_dir: String) -> Result<String, String> {
    let compose_file = format!("{}/docker-compose.yml", docker_dir);
    let output = Command::new("docker-compose")
        .args(["-f", &compose_file, "down"])
        .output()
        .map_err(|e| format!("Erro: {}", e))?;

    if output.status.success() {
        Ok(String::from_utf8_lossy(&output.stdout).to_string())
    } else {
        Err(String::from_utf8_lossy(&output.stderr).to_string())
    }
}

#[tauri::command]
fn get_network_info() -> NetworkInfo {
    let hostname = std::env::var("COMPUTERNAME").unwrap_or_else(|_| "DESKTOP".to_string());

    let mut ips = vec![];

    if let Ok(socket) = UdpSocket::bind("0.0.0.0:0") {
        if socket.connect("8.8.8.8:53").is_ok() {
            if let Ok(addr) = socket.local_addr() {
                ips.push(addr.ip().to_string());
            }
        }
    }

    let ipconfig = Command::new("ipconfig")
        .output()
        .ok()
        .and_then(|o| {
            if o.status.success() {
                Some(String::from_utf8_lossy(&o.stdout).to_string())
            } else {
                None
            }
        })
        .unwrap_or_default();

    for ip_line in ipconfig.lines() {
        let t = ip_line.trim();
        if t.contains("IPv4") || t.contains("Endere�o") {
            if let Some(ip) = t.split(':').nth(1) {
                let ip = ip.trim().to_string();
                if !ip.is_empty() && !ips.contains(&ip) {
                    ips.push(ip);
                }
            }
        }
    }

    let netsh_out = Command::new("netsh")
        .args(["interface", "ip", "show", "config"])
        .output()
        .ok()
        .and_then(|o| {
            if o.status.success() {
                Some(String::from_utf8_lossy(&o.stdout).to_string())
            } else {
                None
            }
        })
        .unwrap_or_default();

    let (adapter, is_static, gateway, subnet) = parse_netsh_config(&netsh_out);

    NetworkInfo {
        hostname,
        ips,
        adapter_name: adapter,
        is_static,
        gateway,
        subnet_mask: subnet,
    }
}

#[tauri::command]
fn set_static_ip(ip: String, mask: String, gateway: String, adapter: String) -> Result<String, String> {
    let out = Command::new("netsh")
        .args([
            "interface", "ip", "set", "address",
            &adapter, "static", &ip, &mask, &gateway, "1",
        ])
        .output()
        .map_err(|e| format!("Erro: {}", e))?;

    if !out.status.success() {
        return Err(String::from_utf8_lossy(&out.stderr).to_string());
    }

    Command::new("netsh")
        .args(["interface", "ip", "set", "dns", &adapter, "static", "8.8.8.8"])
        .output()
        .ok();

    Ok("IP configurado com sucesso!".to_string())
}

#[tauri::command]
fn set_dynamic_ip(adapter: String) -> Result<String, String> {
    let out = Command::new("netsh")
        .args(["interface", "ip", "set", "address", &adapter, "dhcp"])
        .output()
        .map_err(|e| format!("Erro: {}", e))?;

    if out.status.success() {
        Ok("DHCP configurado com sucesso!".to_string())
    } else {
        Err(String::from_utf8_lossy(&out.stderr).to_string())
    }
}

#[tauri::command]
fn start_discovery_server(app_handle: tauri::AppHandle) -> Result<(), String> {
    let handle = app_handle.clone();

    thread::spawn(move || {
        let socket = match UdpSocket::bind(format!("0.0.0.0:{}", DISCOVERY_PORT)) {
            Ok(s) => s,
            Err(e) => {
                eprintln!("Discovery bind error: {}", e);
                return;
            }
        };
        socket.set_read_timeout(Some(std::time::Duration::from_secs(2))).ok();
        let mut buf = [0u8; 1024];

        loop {
            match socket.recv_from(&mut buf) {
                Ok((size, src)) => {
                    if &buf[..size] == DISCOVERY_REQ {
                        let hostname =
                            std::env::var("COMPUTERNAME").unwrap_or_else(|_| "SERVER".to_string());
                        let ip = get_local_ip();
                        let resp = format!("{}:{}:{}:9080", DISCOVERY_RESP_PREFIX, hostname, ip);
                        socket.send_to(resp.as_bytes(), src).ok();
                    }
                }
                Err(_) => {}
            }

            let state = handle.state::<DiscoveryState>();
            if !state.running.load(Ordering::Relaxed) {
                break;
            }
        }
    });

    Ok(())
}

#[tauri::command]
fn stop_discovery_server(app_handle: tauri::AppHandle) -> Result<(), String> {
    let state = app_handle.state::<DiscoveryState>();
    state.running.store(false, Ordering::Relaxed);
    Ok(())
}

#[tauri::command]
fn discover_servers() -> Vec<DiscoveryResult> {
    let mut results = vec![];

    let socket = match UdpSocket::bind("0.0.0.0:0") {
        Ok(s) => s,
        Err(_) => return results,
    };
    socket.set_broadcast(true).ok();
    socket.set_read_timeout(Some(std::time::Duration::from_secs(3))).ok();

    socket
        .send_to(DISCOVERY_REQ, format!("255.255.255.255:{}", DISCOVERY_PORT))
        .ok();

    let mut buf = [0u8; 1024];
    let start = std::time::Instant::now();

    while start.elapsed() < std::time::Duration::from_secs(3) {
        match socket.recv_from(&mut buf) {
            Ok((size, _)) => {
                let msg = String::from_utf8_lossy(&buf[..size]);
                if let Some(rest) = msg.strip_prefix(DISCOVERY_RESP_PREFIX) {
                    let parts: Vec<&str> = rest.split(':').collect();
                    if parts.len() >= 3 {
                        results.push(DiscoveryResult {
                            hostname: parts[0].to_string(),
                            ip: parts[1].to_string(),
                            port: parts.get(2).and_then(|p| p.parse().ok()).unwrap_or(9080),
                        });
                    }
                }
            }
            Err(_) => break,
        }
    }

    results
}

#[tauri::command]
fn get_app_data_dir(app_handle: tauri::AppHandle) -> String {
    app_handle
        .path()
        .app_data_dir()
        .map(|p| p.to_string_lossy().to_string())
        .unwrap_or_else(|_| String::new())
}

#[tauri::command]
fn ping() -> String {
    "ok".to_string()
}

// ─── Tunnel Commands ────────────────────────────────────────────

#[tauri::command]
fn check_cloudflared() -> bool {
    Command::new("cloudflared")
        .arg("--version")
        .output()
        .map(|o| o.status.success())
        .unwrap_or(false)
}

#[tauri::command]
fn check_ngrok() -> bool {
    Command::new("ngrok")
        .arg("--version")
        .output()
        .map(|o| o.status.success())
        .unwrap_or(false)
}

#[tauri::command]
fn start_tunnel(
    tunnel_type: String,
    local_url: String,
    token: String,
    app_handle: tauri::AppHandle,
) -> Result<String, String> {
    let state = app_handle.state::<TunnelState>();

    // Kill existing tunnel
    let mut proc = state.process.lock().unwrap();
    if let Some(ref mut child) = *proc {
        child.kill().ok();
        child.wait().ok();
    }
    *proc = None;
    state.tunnel_url.lock().unwrap().clear();
    state.tunnel_type.lock().unwrap().clear();
    drop(proc);

    let child = if tunnel_type == "cloudflare" {
        let args: Vec<&str> = if token.is_empty() {
            vec!["tunnel", "--url", &local_url]
        } else {
            vec!["tunnel", "run", "--token", &token]
        };
        Command::new("cloudflared")
            .args(&args)
            .stdout(Stdio::piped())
            .stderr(Stdio::piped())
            .spawn()
            .map_err(|e| format!("cloudflared: {}", e))?
    } else if tunnel_type == "ngrok" {
        if !token.is_empty() {
            Command::new("ngrok")
                .args(["config", "add-authtoken", &token])
                .output()
                .ok();
        }
        Command::new("ngrok")
            .args(["http", &local_url, "--log=stdout"])
            .stdout(Stdio::piped())
            .stderr(Stdio::piped())
            .spawn()
            .map_err(|e| format!("ngrok: {}", e))?
    } else {
        return Err(format!("Tipo invalido: {}", tunnel_type));
    };

    {
        let mut proc = state.process.lock().unwrap();
        *proc = Some(child);
    }
    {
        let mut t = state.tunnel_type.lock().unwrap();
        *t = tunnel_type.clone();
    }

    // Background thread to extract URL from output
    let handle = app_handle.clone();
    thread::spawn(move || {
        let state = handle.state::<TunnelState>();
        let mut proc = state.process.lock().unwrap();
        if let Some(ref mut child) = *proc {
            let stderr = child.stderr.take();
            let stdout = child.stdout.take();
            drop(proc);

            let lines: Vec<String> = if tunnel_type == "cloudflare" {
                // cloudflared outputs URL to stderr
                if let Some(stderr) = stderr {
                    let reader = BufReader::new(stderr);
                    reader.lines().filter_map(|l| l.ok()).collect()
                } else {
                    vec![]
                }
            } else {
                // ngrok outputs URL to stdout
                if let Some(stdout) = stdout {
                    let reader = BufReader::new(stdout);
                    reader.lines().filter_map(|l| l.ok()).collect()
                } else if let Some(stderr) = stderr {
                    let reader = BufReader::new(stderr);
                    reader.lines().filter_map(|l| l.ok()).collect()
                } else {
                    vec![]
                }
            };

            for line in &lines {
                if tunnel_type == "cloudflare" && line.contains("trycloudflare.com") {
                    if let Some(start) = line.find("https://") {
                        let rest = &line[start..];
                        let end = rest.find(char::is_whitespace).unwrap_or(rest.len());
                        let url = &rest[..end];
                        *state.tunnel_url.lock().unwrap() = url.to_string();
                        break;
                    }
                } else if tunnel_type == "ngrok" && line.contains("ngrok-free.app") {
                    if let Some(start) = line.find("https://") {
                        let rest = &line[start..];
                        let end = rest.find(char::is_whitespace).unwrap_or(rest.len());
                        let url = &rest[..end];
                        *state.tunnel_url.lock().unwrap() = url.to_string();
                        break;
                    }
                }
            }
        }
    });

    thread::sleep(std::time::Duration::from_millis(1500));

    let url = state.tunnel_url.lock().unwrap().clone();
    if url.is_empty() {
        Ok("Tunnel iniciado! URL sera detectada em instantes.".to_string())
    } else {
        Ok(url)
    }
}

#[tauri::command]
fn stop_tunnel(app_handle: tauri::AppHandle) -> Result<(), String> {
    let state = app_handle.state::<TunnelState>();
    let mut proc = state.process.lock().unwrap();
    if let Some(ref mut child) = *proc {
        child.kill().map_err(|e| format!("Erro: {}", e))?;
        child.wait().ok();
    }
    *proc = None;
    state.tunnel_url.lock().unwrap().clear();
    state.tunnel_type.lock().unwrap().clear();
    Ok(())
}

#[tauri::command]
fn get_tunnel_info(app_handle: tauri::AppHandle) -> TunnelInfo {
    let state = app_handle.state::<TunnelState>();
    let mut proc = state.process.lock().unwrap();
    let running = proc
        .as_mut()
        .map(|c| c.try_wait().ok().flatten().is_none())
        .unwrap_or(false);
    let url = state.tunnel_url.lock().unwrap().clone();
    let tunnel_type = state.tunnel_type.lock().unwrap().clone();
    TunnelInfo {
        running,
        url,
        tunnel_type,
    }
}

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    tauri::Builder::default()
        .manage(DiscoveryState {
            running: AtomicBool::new(false),
        })
        .manage(TunnelState {
            process: Mutex::new(None),
            tunnel_url: Mutex::new(String::new()),
            tunnel_type: Mutex::new(String::new()),
        })
        .plugin(tauri_plugin_http::init())
        .plugin(tauri_plugin_store::Builder::default().build())
        .invoke_handler(tauri::generate_handler![
            check_docker_installed,
            check_docker_running,
            get_containers,
            docker_compose_up,
            docker_compose_down,
            get_network_info,
            set_static_ip,
            set_dynamic_ip,
            start_discovery_server,
            stop_discovery_server,
            discover_servers,
            get_app_data_dir,
            ping,
            check_cloudflared,
            check_ngrok,
            start_tunnel,
            stop_tunnel,
            get_tunnel_info,
        ])
        .setup(|app| {
            #[cfg(debug_assertions)]
            {
                let window = app.get_webview_window("main").unwrap();
                window.open_devtools();
            }
            Ok(())
        })
        .run(tauri::generate_context!())
        .expect("Erro ao iniciar SCHF Core");
}
