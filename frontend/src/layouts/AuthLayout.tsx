import { Outlet } from 'react-router-dom'

export function AuthLayout() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-muted">
      <div className="w-full max-w-md p-8">
        <div className="text-center mb-8">
          <h1 className="text-2xl font-bold text-primary">
            SCHF
          </h1>
          <p className="text-muted-foreground mt-1">Sistema Financeiro</p>
        </div>
        <Outlet />
      </div>
    </div>
  )
}

