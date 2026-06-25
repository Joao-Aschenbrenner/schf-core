import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from './Card'

describe('Card', () => {
  it('renders card container', () => {
    render(<Card data-testid="card">Content</Card>)
    const card = screen.getByTestId('card')
    expect(card).toBeInTheDocument()
    expect(card.className).toContain('rounded-lg')
    expect(card.className).toContain('border')
    expect(card.className).toContain('shadow-sm')
  })

  it('applies custom className', () => {
    render(<Card className="custom-card" data-testid="card">Content</Card>)
    expect(screen.getByTestId('card').className).toContain('custom-card')
  })
})

describe('CardHeader', () => {
  it('renders header', () => {
    render(<Card><CardHeader data-testid="header">Header</CardHeader></Card>)
    const header = screen.getByTestId('header')
    expect(header).toBeInTheDocument()
    expect(header.className).toContain('p-6')
  })
})

describe('CardTitle', () => {
  it('renders title', () => {
    render(<Card><CardTitle data-testid="title">Title</CardTitle></Card>)
    const title = screen.getByTestId('title')
    expect(title).toBeInTheDocument()
    expect(title.tagName).toBe('H3')
    expect(title.className).toContain('font-semibold')
  })
})

describe('CardDescription', () => {
  it('renders description', () => {
    render(<Card><CardDescription data-testid="desc">Description</CardDescription></Card>)
    const desc = screen.getByTestId('desc')
    expect(desc).toBeInTheDocument()
    expect(desc.tagName).toBe('P')
    expect(desc.className).toContain('text-muted-foreground')
  })
})

describe('CardContent', () => {
  it('renders content', () => {
    render(<Card><CardContent data-testid="content">Content</CardContent></Card>)
    const content = screen.getByTestId('content')
    expect(content).toBeInTheDocument()
    expect(content.className).toContain('p-6')
  })
})
