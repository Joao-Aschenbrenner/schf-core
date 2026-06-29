import * as React from 'react'
import { cn } from '../../utils/cn'

type DropdownMenuContextValue = {
  open: boolean
  setOpen: React.Dispatch<React.SetStateAction<boolean>>
}

const DropdownMenuContext = React.createContext<DropdownMenuContextValue | null>(null)

function useDropdownMenu() {
  const context = React.useContext(DropdownMenuContext)
  if (!context) {
    throw new Error('DropdownMenu components must be used inside DropdownMenu')
  }
  return context
}

function DropdownMenu({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = React.useState(false)

  return (
    <DropdownMenuContext.Provider value={{ open, setOpen }}>
      <div className="relative inline-block">{children}</div>
    </DropdownMenuContext.Provider>
  )
}

function DropdownMenuTrigger({ children, asChild = false }: { children: React.ReactNode; asChild?: boolean }) {
  const { setOpen } = useDropdownMenu()

  if (asChild && React.isValidElement(children)) {
    return React.cloneElement(children as React.ReactElement<any>, {
      onClick: (event: React.MouseEvent) => {
        children.props.onClick?.(event)
        setOpen((current) => !current)
      },
    })
  }

  return <button onClick={() => setOpen((current) => !current)}>{children}</button>
}

function DropdownMenuContent({
  align = 'start',
  className,
  children,
}: {
  align?: 'start' | 'end'
  className?: string
  children: React.ReactNode
}) {
  const { open } = useDropdownMenu()
  if (!open) return null

  return (
    <div
      className={cn(
        'absolute z-50 mt-2 min-w-40 rounded-md border bg-popover p-1 text-popover-foreground shadow-md',
        align === 'end' ? 'right-0' : 'left-0',
        className
      )}
    >
      {children}
    </div>
  )
}

function DropdownMenuItem({ className, onClick, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  const { setOpen } = useDropdownMenu()

  return (
    <div
      role="menuitem"
      tabIndex={0}
      className={cn('flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent', className)}
      onClick={(event) => {
        onClick?.(event)
        setOpen(false)
      }}
      {...props}
    />
  )
}

export { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem }
