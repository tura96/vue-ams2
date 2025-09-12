export interface AssetItem {
  id: string
  assetTag: string
  model: string
  serialNumber: string
  status: AssetStatus
  assignedTo?: string
  location?: string
  warrantyExpiry: string
  notes?: string
  purchaseDate?: string
  cost?: number
  rfidTag?: string
  deployedTo?: string
}

export type AssetStatus = 
  | 'new' 
  | 'staging' 
  | 'ready' 
  | 'deployed' 
  | 'defected' 
  | 'maintenance' 
  | 'archived' 
  | 'disposed' 
  | 'return'

export interface AssetFilter {
  status?: AssetStatus
  model?: string
  store?: string
  search?: string
}

export interface AssetModel {
  id: string
  name: string
  manufacturer: string
  category: string
}