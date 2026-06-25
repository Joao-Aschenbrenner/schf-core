import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Input } from './Input'

describe('Input', () => {
  it('renders input element', () => {
    render(<Input data-testid="input" />)
    expect(screen.getByTestId('input')).toBeInTheDocument()
  })

  it('renders with placeholder', () => {
    render(<Input placeholder="Enter text" data-testid="input" />)
    expect(screen.getByPlaceholderText('Enter text')).toBeInTheDocument()
  })

  it('renders with default type text', () => {
    render(<Input data-testid="input" />)
    const input = screen.getByTestId('input')
    expect(input.getAttribute('type')).toBeNull()
    expect(input).not.toHaveAttribute('type', 'password')
  })

  it('renders with custom type', () => {
    render(<Input type="password" data-testid="input" />)
    expect(screen.getByTestId('input')).toHaveAttribute('type', 'password')
  })

  it('renders with date type', () => {
    render(<Input type="date" data-testid="input" />)
    expect(screen.getByTestId('input')).toHaveAttribute('type', 'date')
  })

  it('can be disabled', () => {
    render(<Input disabled data-testid="input" />)
    expect(screen.getByTestId('input')).toBeDisabled()
  })

  it('applies custom className', () => {
    render(<Input className="custom-input" data-testid="input" />)
    expect(screen.getByTestId('input').className).toContain('custom-input')
  })

  it('handles value changes', async () => {
    const user = userEvent.setup()
    render(<Input data-testid="input" />)
    const input = screen.getByTestId('input')
    await user.type(input, 'hello')
    expect(input).toHaveValue('hello')
  })

  it('renders with value', () => {
    render(<Input value="test value" readOnly data-testid="input" />)
    expect(screen.getByTestId('input')).toHaveValue('test value')
  })

  it('forwards ref', () => {
    const ref = { current: null }
    render(<Input ref={ref} data-testid="input" />)
    expect(ref.current).toBeInstanceOf(HTMLInputElement)
  })
})
