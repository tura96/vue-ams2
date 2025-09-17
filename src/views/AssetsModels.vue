<template>
  <div class="models-page">
    <!-- Page Header -->
    <div class="models-page__header">
      <h1 class="models-page__title">Asset Models Management</h1>
      <button class="btn btn--primary" @click="openAddModal">
        <!-- <img src="@/assets/images/icons/mdi_plus.svg" alt="Add" class="btn__icon"> -->
        Add New Model
      </button>
    </div>

    <!-- Models List -->
    <div class="models-list" v-if="!loading && !error">
      <div class="models-list__header">
        <div class="search-box">
          <input 
            type="text" 
            class="search-box__input" 
            placeholder="Search models..."
            v-model="searchQuery"
          >
          <img src="@/assets/images/icons/mdi_search.svg" alt="Search" class="search-box__icon">
        </div>
      </div>

      <div class="models-grid">
        <div 
          v-for="model in filteredModels" 
          :key="model.id" 
          class="model-card"
        >
          <div class="model-card__header">
            <h3 class="model-card__name">{{ model.name }}</h3>
            <div class="model-card__actions">
              <button 
                class="btn-icon btn-icon--edit" 
                @click="editModel(model)"
                title="Edit"
              >
                <!-- <img src="@/assets/images/icons/mdi_edit-outline.svg" alt="Edit"> -->
              </button>
              <button 
                class="btn-icon btn-icon--delete" 
                @click="confirmDelete(model)"
                title="Delete"
              >
                <!-- <img src="@/assets/images/icons/mdi_delete-outline.svg" alt="Delete"> -->
              </button>
            </div>
          </div>
          
          <div class="model-card__content">
            <div class="model-card__field">
              <span class="model-card__label">Category:</span>
              <span class="model-card__value">{{ model.category || 'N/A' }}</span>
            </div>
            <div class="model-card__field">
              <span class="model-card__label">Manufacturer:</span>
              <span class="model-card__value">{{ model.manufacturer || 'N/A' }}</span>
            </div>
            <div class="model-card__field" v-if="model.description">
              <span class="model-card__label">Description:</span>
              <span class="model-card__value">{{ model.description }}</span>
            </div>
            <div class="model-card__field">
              <span class="model-card__label">Status:</span>
              <span class="model-card__badge" :class="`model-card__badge--${model.status}`">
                {{ model.status }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="filteredModels.length === 0" class="no-results">
        <p>No models found{{ searchQuery ? ` for "${searchQuery}"` : '' }}</p>
      </div>
    </div>

    <div v-else-if="loading" class="loading">Loading models...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <!-- Add/Edit Modal -->
    <div class="modal-overlay" v-if="isModalOpen" @click="closeModal">
      <div class="modal" @click.stop>
        <div class="modal__header">
          <h2 class="modal__title">
            {{ isEditing ? 'Edit Model' : 'Add New Model' }}
          </h2>
          <button class="modal__close" @click="closeModal">
            <!-- <img src="@/assets/images/icons/mdi_close.svg" alt="Close"> -->
          </button>
        </div>

        <form class="modal__content" @submit.prevent="saveModel">
          <div class="input-floating">
            <input 
              type="text" 
              id="modelName" 
              class="input-floating__field" 
              :class="{ 'input-floating__field--has-value': modelForm.name }"
              placeholder="Model Name"
              v-model="modelForm.name"
              required
            >
            <label for="modelName" class="input-floating__label">Model Name *</label>
          </div>

          <CustomSelect 
            v-model="modelForm.category"
            label="Category"
            :options="categoryOptions"
          />

          <CustomSelect 
            v-model="modelForm.manufacturer"
            label="Manufacturer"
            :options="manufacturerOptions"
          />

          <CustomSelect 
            v-model="modelForm.status"
            label="Status"
            :options="modelStatusOptions"
          />

          <div class="input-floating">
            <textarea 
              id="modelDescription" 
              class="input-floating__field" 
              :class="{ 'input-floating__field--has-value': modelForm.description }"
              rows="3"
              placeholder="Description"
              v-model="modelForm.description"
            ></textarea>
            <label for="modelDescription" class="input-floating__label">Description</label>
          </div>

          <div class="modal__actions">
            <button type="button" class="btn btn--third" @click="closeModal">
              Cancel
            </button>
            <button type="submit" class="btn btn--primary">
              {{ isEditing ? 'Update' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" v-if="isDeleteModalOpen" @click="closeDeleteModal">
      <div class="modal modal--small" @click.stop>
        <div class="modal__header">
          <h2 class="modal__title">Confirm Delete</h2>
        </div>
        <div class="modal__content">
          <p>Are you sure you want to delete the model "{{ modelToDelete?.name }}"?</p>
          <p class="text-warning">This action cannot be undone.</p>
          
          <div class="modal__actions">
            <button type="button" class="btn btn--third" @click="closeDeleteModal">
              Cancel
            </button>
            <button type="button" class="btn btn--danger" @click="deleteModel">
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Alert Modal -->
    <AlertModal
      :is-open="isAlertOpen"
      :message="alertMessage"
      :type="alertType"
      @close="closeAlert"
    />
  </div>
</template>

<script lang="ts" setup>
import { ref, reactive, onMounted, computed } from 'vue'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import AlertModal from '@/components/modals/AlertModal.vue'
// import { amsApi } from '../services/amsApi'

interface AssetModel {
  id: number
  name: string
  category: string
  manufacturer: string
  description?: string
  status: 'active' | 'inactive' | 'discontinued'
  date_created?: string
  date_modified?: string
}

interface ModelFormData {
  name: string
  category: string
  manufacturer: string
  description: string
  status: 'active' | 'inactive' | 'discontinued'
}

interface SelectOption {
  value: string
  label: string
}

const loading = ref(true)
const error = ref<string | null>(null)
const searchQuery = ref('')
const models = ref<AssetModel[]>([])

// Modal states
const isModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const isEditing = ref(false)
const editingModelId = ref<number | null>(null)
const modelToDelete = ref<AssetModel | null>(null)

// Alert states
const isAlertOpen = ref(false)
const alertMessage = ref('')
const alertType = ref<'success' | 'error'>('error')

// Form data
const modelForm = reactive<ModelFormData>({
  name: '',
  category: '',
  manufacturer: '',
  description: '',
  status: 'active'
})

// Dropdown options
const categoryOptions = ref<SelectOption[]>([])
const manufacturerOptions = ref<SelectOption[]>([])
const modelStatusOptions = ref<SelectOption[]>([
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'discontinued', label: 'Discontinued' }
])

// Computed
const filteredModels = computed(() => {
  if (!searchQuery.value) return models.value
  return models.value.filter(model =>
    model.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    model.category.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    model.manufacturer.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

// Methods
const showAlert = (message: string, type: 'success' | 'error') => {
  alertMessage.value = message
  alertType.value = type
  isAlertOpen.value = true
}

const closeAlert = () => {
  isAlertOpen.value = false
  alertMessage.value = ''
  alertType.value = 'error'
}

const resetForm = () => {
  modelForm.name = ''
  modelForm.category = ''
  modelForm.manufacturer = ''
  modelForm.description = ''
  modelForm.status = 'active'
}

const openAddModal = () => {
  isEditing.value = false
  editingModelId.value = null
  resetForm()
  isModalOpen.value = true
}

const editModel = (model: AssetModel) => {
  isEditing.value = true
  editingModelId.value = model.id
  modelForm.name = model.name
  modelForm.category = model.category
  modelForm.manufacturer = model.manufacturer
  modelForm.description = model.description || ''
  modelForm.status = model.status
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
  resetForm()
  isEditing.value = false
  editingModelId.value = null
}

const confirmDelete = (model: AssetModel) => {
  modelToDelete.value = model
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false
  modelToDelete.value = null
}

// API Methods
const fetchModels = async () => {
  try {
    // This would be your actual API call
    // For now, using mock data
    const mockModels: AssetModel[] = [
      {
        id: 1,
        name: 'Dell Latitude 5520',
        category: 'Laptop',
        manufacturer: 'Dell',
        description: 'Business laptop with Intel i5 processor',
        status: 'active'
      },
      {
        id: 2,
        name: 'HP ProDesk 600',
        category: 'Desktop',
        manufacturer: 'HP',
        description: 'Compact desktop computer',
        status: 'active'
      },
      {
        id: 3,
        name: 'Canon PIXMA MG2520',
        category: 'Printer',
        manufacturer: 'Canon',
        description: 'All-in-one inkjet printer',
        status: 'discontinued'
      }
    ]
    
    models.value = mockModels
  } catch (err) {
    console.error('Error fetching models:', err)
    error.value = 'Failed to load models'
  }
}

const fetchDropdownData = async () => {
  try {
    // Mock data for dropdowns
    categoryOptions.value = [
      { value: 'Laptop', label: 'Laptop' },
      { value: 'Desktop', label: 'Desktop' },
      { value: 'Monitor', label: 'Monitor' },
      { value: 'Printer', label: 'Printer' },
      { value: 'Network Equipment', label: 'Network Equipment' },
      { value: 'Mobile Device', label: 'Mobile Device' }
    ]

    manufacturerOptions.value = [
      { value: 'Dell', label: 'Dell' },
      { value: 'HP', label: 'HP' },
      { value: 'Lenovo', label: 'Lenovo' },
      { value: 'Apple', label: 'Apple' },
      { value: 'Canon', label: 'Canon' },
      { value: 'Cisco', label: 'Cisco' },
      { value: 'Samsung', label: 'Samsung' }
    ]
  } catch (err) {
    console.error('Error fetching dropdown data:', err)
  }
}

const saveModel = async () => {
  if (!modelForm.name.trim()) {
    showAlert('Model name is required', 'error')
    return
  }

  try {
    if (isEditing.value && editingModelId.value) {
      // Update existing model
      const updatedModel: AssetModel = {
        id: editingModelId.value,
        name: modelForm.name.trim(),
        category: modelForm.category,
        manufacturer: modelForm.manufacturer,
        description: modelForm.description.trim() || undefined,
        status: modelForm.status
      }
      
      // Update in local array (replace with actual API call)
      const index = models.value.findIndex(m => m.id === editingModelId.value)
      if (index !== -1) {
        models.value[index] = updatedModel
      }
      
      showAlert('Model updated successfully!', 'success')
    } else {
      // Create new model
      const newModel: AssetModel = {
        id: Date.now(), // Mock ID - replace with actual API response
        name: modelForm.name.trim(),
        category: modelForm.category,
        manufacturer: modelForm.manufacturer,
        description: modelForm.description.trim() || undefined,
        status: modelForm.status
      }
      
      models.value.unshift(newModel)
      showAlert('Model created successfully!', 'success')
    }
    
    closeModal()
  } catch (err) {
    console.error('Error saving model:', err)
    showAlert('Failed to save model', 'error')
  }
}

const deleteModel = async () => {
  if (!modelToDelete.value) return

  try {
    // Remove from local array (replace with actual API call)
    const index = models.value.findIndex(m => m.id === modelToDelete.value!.id)
    if (index !== -1) {
      models.value.splice(index, 1)
    }
    
    showAlert('Model deleted successfully!', 'success')
    closeDeleteModal()
  } catch (err) {
    console.error('Error deleting model:', err)
    showAlert('Failed to delete model', 'error')
  }
}

// Initialize
onMounted(async () => {
  loading.value = true
  error.value = null
  
  try {
    await Promise.all([
      fetchModels(),
      fetchDropdownData()
    ])
  } catch (err: any) {
    error.value = `Failed to load data: ${err.message}`
  } finally {
    loading.value = false
  }
})
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/forms.scss";

.models-page {
  padding: 1.5rem;

  &__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
  }

  &__title {
    font-size: 1.75rem;
    font-weight: 600;
    color: #1a202c;
    margin: 0;
  }
}

.models-list {
  &__header {
    margin-bottom: 1.5rem;
  }
}

.search-box {
  position: relative;
  max-width: 400px;

  &__input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 3rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;

    &:focus {
      outline: none;
      border-color: #3182ce;
      box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
    }
  }

  &__icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    width: 1.25rem;
    height: 1.25rem;
    opacity: 0.5;
  }
}

.models-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.model-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.2s;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
  }

  &__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
  }

  &__name {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1a202c;
    margin: 0;
    flex: 1;
  }

  &__actions {
    display: flex;
    gap: 0.5rem;
    margin-left: 1rem;
  }

  &__content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  &__field {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  &__label {
    font-weight: 500;
    color: #4a5568;
    min-width: 80px;
  }

  &__value {
    color: #1a202c;
    flex: 1;
  }

  &__badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
    text-transform: capitalize;

    &--active {
      background-color: #c6f6d5;
      color: #22543d;
    }

    &--inactive {
      background-color: #fed7c3;
      color: #c05621;
    }

    &--discontinued {
      background-color: #fed7d7;
      color: #c53030;
    }
  }
}

.btn-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;

  img {
    width: 1rem;
    height: 1rem;
  }

  &--edit {
    background-color: #ebf8ff;
    color: #3182ce;

    &:hover {
      background-color: #bee3f8;
    }
  }

  &--delete {
    background-color: #fed7d7;
    color: #c53030;

    &:hover {
      background-color: #feb2b2;
    }
  }
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;

  &--small {
    max-width: 400px;
  }

  &__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 1.5rem 1rem;
    border-bottom: 1px solid #e2e8f0;
  }

  &__title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1a202c;
    margin: 0;
  }

  &__close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;

    &:hover {
      background-color: #f7fafc;
    }

    img {
      width: 1.25rem;
      height: 1.25rem;
    }
  }

  &__content {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  &__actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1rem;
  }
}

.text-warning {
  color: #c05621;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.no-results {
  text-align: center;
  padding: 3rem 1rem;
  color: #4a5568;
  
  p {
    font-size: 1.125rem;
    margin: 0;
  }
}

.loading {
  text-align: center;
  padding: 3rem;
  color: #4a5568;
  font-size: 1.125rem;
}

.error {
  text-align: center;
  padding: 3rem;
  color: #c53030;
  background: #fff5f5;
  border: 1px solid #feb2b2;
  border-radius: 8px;
  margin: 1rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;

  &__icon {
    width: 1rem;
    height: 1rem;
  }

  &--primary {
    background-color: #3182ce;
    color: white;

    &:hover {
      background-color: #2c5aa0;
    }
  }

  &--third {
    background-color: #e2e8f0;
    color: #4a5568;

    &:hover {
      background-color: #cbd5e0;
    }
  }

  &--danger {
    background-color: #c53030;
    color: white;

    &:hover {
      background-color: #9c2626;
    }
  }
}
</style>