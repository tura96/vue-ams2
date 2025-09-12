export interface TableColumn {
  key: string
  label: string
  sortable?: boolean
  minWidth?: string
}

export interface PaginationState {
  currentPage: number
  itemsPerPage: number
  totalItems: number
}

export interface NotificationState {
  message: string
  type: 'success' | 'error' | 'warning' | 'info'
  duration?: number
}