import type { AxiosResponse } from 'axios';
import axios from 'axios'
import Cookies from 'js-cookie' // Add this import


// Types
export interface Asset {
  id: number
  title: string
  description?: string
  asset_tag: string
  model: string
  serial_number: string
  status: AssetStatus
  assigned_to?: string
  location?: string
  store?: string
  warranty_expiry?: string
  notes?: string
  purchase_date?: string
  purchase_cost?: string | number | undefined
  date_created: string
  date_modified: string
  rfid_tag:string
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

export interface AssetCreateData {
  title: string
  description?: string
  asset_tag: string
  model: string
  serial_number: string
  status: AssetStatus
  assigned_to?: string
  location?: string
  store?: string
  warranty_expiry?: string
  notes?: string
  purchase_date?: string
  purchase_cost?: any
  rfid_tag?:string
}

export interface AssetUpdateData extends Partial<AssetCreateData> {
  id: number
}

export interface AssetFilters {
  search?: string
  status?: AssetStatus | ''
  model?: string
  store?: string
  location?: string
  assigned_to?: string
}

export interface AssetSorting {
  sort_by?: 'date' | 'title' | 'asset_tag' | 'model' | 'status' | 'warranty_expiry'
  sort_order?: 'ASC' | 'DESC'
}

export interface PaginationParams {
  page?: number
  per_page?: number
}

export interface AssetListParams extends AssetFilters, AssetSorting, PaginationParams {}

export interface PaginationInfo {
  current_page: number
  per_page: number
  total_items: number
  total_pages: number
}

export interface ApiResponse<T> {
  success: boolean
  data?: T
  message?: string
  pagination?: PaginationInfo
}

export interface AssetListResponse extends ApiResponse<Asset[]> {
  pagination: PaginationInfo
}

export interface AssetResponse extends ApiResponse<Asset> {}

export interface BulkDeployRequest {
  asset_ids: number[]
}

export interface BulkDeployResponse extends ApiResponse<null> {
  updated_count: number
}

export interface StatusesResponse extends ApiResponse<Record<string, string>> {}

export interface ModelsResponse extends ApiResponse<string[]> {}

export interface LocationsResponse extends ApiResponse<string[]> {}

class AMSApiService {
  private baseURL: string

  constructor(baseURL?: string) {
    this.baseURL = baseURL || `${import.meta.env.VITE_API_URL}/ams/v1`
  }

  private getAuthHeaders() {
    const token = Cookies.get('ams_token')
    // console.log('Token from cookie:', token ? 'Present' : 'Missing') // Debug log
    
    if (!token) {
      console.warn('No ams_token cookie found')
      return {}
    }
    
    const headers = { 
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
    return headers
  }

  // Asset Listing with Search, Sort, Filter, and Pagination
  async getAssets(params: AssetListParams = {}): Promise<AssetListResponse> {
    try {
      const queryParams = new URLSearchParams()
      
      // Pagination
      if (params.page) queryParams.append('page', params.page.toString())
      if (params.per_page) queryParams.append('per_page', params.per_page.toString())
      
      // Filters
      if (params.search) queryParams.append('search', params.search)
      if (params.status) queryParams.append('status', params.status)
      if (params.model) queryParams.append('model', params.model)
      if (params.store) queryParams.append('store', params.store)
      if (params.location) queryParams.append('location', params.location)
      if (params.assigned_to) queryParams.append('assigned_to', params.assigned_to)
      
      // Sorting
      if (params.sort_by) queryParams.append('sort_by', params.sort_by)
      if (params.sort_order) queryParams.append('sort_order', params.sort_order)

      const response: AxiosResponse<AssetListResponse> = await axios.get(
        `${this.baseURL}/assets?${queryParams.toString()}`,
        { headers: this.getAuthHeaders() }
      )

      return response.data
    } catch (error: any) {
      throw this.handleApiError(error)
    }
  }

  // Get Single Asset
  async getAsset(id: number): Promise<AssetResponse> {
    try {
      const response: AxiosResponse<AssetResponse> = await axios.get(
        `${this.baseURL}/asset/${id}`,
        { headers: this.getAuthHeaders() }
      )

      return response.data
    } catch (error: any) {
      throw this.handleApiError(error)
    }
  }

  // Create New Asset
  async createAsset(assetData: AssetCreateData): Promise<AssetResponse> {
    try {
      const response: AxiosResponse<AssetResponse> = await axios.post(
        `${this.baseURL}/assets`,
        assetData,
        { headers: { ...this.getAuthHeaders(), 'Content-Type': 'application/json' } }
      )

      return response.data
    } catch (error: any) {
      throw this.handleApiError(error)
    }
  }

  // Update Asset
  async updateAsset(id: number, assetData: Partial<AssetCreateData>): Promise<AssetResponse> {
    try {
      const response: AxiosResponse<AssetResponse> = await axios.put(
        `${this.baseURL}/assets/${id}`,
        assetData,
        { headers: { ...this.getAuthHeaders(), 'Content-Type': 'application/json' } }
      )

      return response.data
    } catch (error: any) {
      throw this.handleApiError(error)
    }
  }

  // Delete Asset
  async deleteAsset(id: number): Promise<ApiResponse<null>> {
    try {
      const response: AxiosResponse<ApiResponse<null>> = await axios.delete(
        `${this.baseURL}/assets/${id}`,
        { headers: this.getAuthHeaders() }
      )

      return response.data
    } catch (error: any) {
      throw this.handleApiError(error)
    }
  }

  // Bulk Deploy Assets
  async bulkDeployAssets(assetIds: number[]): Promise<BulkDeployResponse> {
    try {
      const response: AxiosResponse<BulkDeployResponse> = await axios.post(
        `${this.baseURL}/assets/bulk-deploy`,
        { asset_ids: assetIds },
        { headers: { ...this.getAuthHeaders(), 'Content-Type': 'application/json' } }
      )

      return response.data
    } catch (error: any) {
      throw this.handleApiError(error)
    }
  }

  // Metadata Endpoints
  async getStatuses(): Promise<StatusesResponse> {
    try {
      const response: AxiosResponse<StatusesResponse> = await axios.get(
        `${this.baseURL}/metadata/statuses`,
        { headers: this.getAuthHeaders() }
      )

      return response.data
    } catch (error: any) {
      throw this.handleApiError(error)
    }
  }

  async getModels(): Promise<ModelsResponse> {
    try {
      const response: AxiosResponse<ModelsResponse> = await axios.get(
        `${this.baseURL}/metadata/models`,
        { headers: this.getAuthHeaders() }
      )

      return response.data
    } catch (error: any) {
      throw this.handleApiError(error)
    }
  }

  async getLocations(): Promise<LocationsResponse> {
    try {
      const response: AxiosResponse<LocationsResponse> = await axios.get(
        `${this.baseURL}/metadata/locations`,
        { headers: this.getAuthHeaders() }
      )

      return response.data
    } catch (error: any) {
      throw this.handleApiError(error)
    }
  }

    async getAssigned(): Promise<LocationsResponse> {
    try {
      const response: AxiosResponse<LocationsResponse> = await axios.get(
        `${this.baseURL}/metadata/assigned`,
        { headers: this.getAuthHeaders() }
      )

      return response.data
    } catch (error: any) {
      throw this.handleApiError(error)
    }
  }

  // Helper method to handle API errors
  private handleApiError(error: any): Error {
    if (error.response?.data?.message) {
      return new Error(error.response.data.message)
    } else if (error.response?.data?.code) {
      switch (error.response.data.code) {
        case 'asset_not_found':
          return new Error('Asset not found')
        case 'missing_token':
          return new Error('Authentication required')
        case 'invalid_token':
          return new Error('Invalid authentication token')
        case 'insufficient_permissions':
          return new Error('Insufficient permissions')
        default:
          return new Error(error.response.data.message || 'API request failed')
      }
    } else if (error.message) {
      return new Error(error.message)
    } else {
      return new Error('Unknown API error')
    }
  }
}

// Export singleton instance
export const amsApi = new AMSApiService()

// Export class for custom instances
export default AMSApiService