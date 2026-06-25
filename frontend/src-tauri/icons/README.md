# Ãcones do Aplicativo

Para compilar o app desktop, os seguintes Ã­cones sÃ£o necessÃ¡rios:

- `32x32.png` â€” Ãcone 32x32 PNG
- `128x128.png` â€” Ãcone 128x128 PNG
- `128x128@2x.png` â€” Ãcone 256x256 PNG (retina)
- `icon.icns` â€” Ãcone macOS (nÃ£o obrigatÃ³rio para Windows)
- `icon.ico` â€” Ãcone Windows ICO (contÃ©m 16x16, 32x32, 48x48, 256x256)
- `installer-header.bmp` â€” Banner do instalador NSIS (150x57 BMP, 24-bit)
- `installer-sidebar.bmp` â€” Sidebar do instalador NSIS (164x314 BMP, 24-bit)

## Gerando Ã­cones

Use o Tauri CLI para gerar todos os Ã­cones a partir de uma imagem fonte:

```bash
npx tauri icon path/to/source-icon.png
```

A imagem fonte deve ser:
- PNG ou SVG
- MÃ­nimo 1024x1024 pixels
- Quadrada, sem transparÃªncia no fundo
- Representando a identidade visual da SCHF

## Identidade visual sugerida

- Cor primÃ¡ria: azul hospital (#1a5276 ou similar)
- Elemento: cruz vermelha + caduceu estilizado
- Texto: "SC" ou "SCHF"

