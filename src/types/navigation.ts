export interface NavItem {
  id: string
  name: string
  icon: string
  route?: string
  expandable?: boolean
  expanded?: boolean
  children?: NavSubItem[]
}

export interface NavSubItem {
  id: string
  name: string
  icon: string
  route: string
  active?: boolean
}