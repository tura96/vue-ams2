<template>
  <transition name="modal-slide">
    <div v-if="isOpen" class="modal-overlay" @click="handleOverlayClick">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">Bulk Deploy Assets</h3>
        </div>
        
        <div class="modal-body">
          <p class="deploy-info">You are deploying {{ selectedAssets.length }} assets:</p>
          
          <div class="asset-list">
            <div v-for="asset in selectedAssets" :key="asset.id" class="asset-item">
              <span class="asset-tag">{{ asset.asset_tag }}</span>
              <span class="asset-title"> - {{ asset.model }}</span>
            </div>
          </div>

          <div class="form-group">
            <CustomSelect 
              v-model="formData.store"
              label="Store"
              :options="storeOptions"
              placeholder=""
              :class="{ 'error': errors.store }"
              @update:modelValue="validateField('store')"
            />
            <span v-if="errors.store" class="error-message">{{ errors.store }}</span>
          </div>

          <div class="form-group">
            <CustomSelect 
              v-model="formData.location"
              label="Location"
              :options="locationOptions"
              placeholder=""
              :class="{ 'error': errors.location }"
              @update:modelValue="validateField('location')"
            />
            <span v-if="errors.location" class="error-message">{{ errors.location }}</span>
          </div>

          <div class="form-group">
            <label for="notes" class="form-label">Notes</label>
            <textarea 
              id="notes"
              v-model="formData.notes" 
              class="form-textarea"
              rows="3"
              placeholder="Optional deployment notes..."
            ></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button 
            class="btn btn--secondary" 
            @click="handleCancel"
            :disabled="loading"
          >
            CANCEL
          </button>
          <button 
            class="btn btn--primary" 
            @click="handleConfirm"
            :disabled="loading || !isValid"
          >
            <span v-if="loading">Deploying...</span>
            <span v-else>CONFIRM DEPLOY</span>
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { reactive, computed, watch } from 'vue'
import type { Asset } from '../../services/amsApi'
import CustomSelect from '@/components/ui/CustomSelect.vue'

interface Option {
  value: string
  label: string
}

interface Props {
  isOpen: boolean
  selectedAssets: Asset[]
  storeOptions: Option[]
  locationOptions: Option[]
  loading?: boolean
}

interface BulkDeployData {
  store: string
  location: string
  notes?: string
}

interface Emits {
  (e: 'confirm', data: BulkDeployData): void
  (e: 'cancel'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const formData = reactive<BulkDeployData>({
  store: '',
  location: '',
  notes: ''
})

const errors = reactive({
  store: '',
  location: ''
})

const isValid = computed(() => {
  return formData.store && formData.location && !errors.store && !errors.location
})

// Validate individual field
function validateField(field: 'store' | 'location') {
  if (field === 'store') {
    errors.store = formData.store ? '' : 'Store is required'
  } else if (field === 'location') {
    errors.location = formData.location ? '' : 'Location is required'
  }
}

const handleConfirm = () => {
  validateField('store')
  validateField('location')
  if (isValid.value) {
    const deployData: BulkDeployData = {
      store: formData.store,
      location: formData.location
    }
    if (formData.notes) {
      deployData.notes = formData.notes
    }
    emit('confirm', deployData)
  }
}

const handleCancel = () => {
  // Reset form
  formData.store = ''
  formData.location = ''
  formData.notes = ''
  errors.store = ''
  errors.location = ''
  emit('cancel')
}

const handleOverlayClick = (event: MouseEvent) => {
  if (event.target === event.currentTarget) {
    handleCancel()
  }
}

// Reset form when modal opens
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    formData.store = ''
    formData.location = ''
    formData.notes = ''
    errors.store = ''
    errors.location = ''
  }
})
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/forms.scss";

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

.modal-content {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-slide-enter-active,
.modal-slide-leave-active {
  transition: all 0.3s ease;
}

.modal-slide-enter-from,
.modal-slide-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

.modal-slide-enter-to,
.modal-slide-leave-from {
  opacity: 1;
  transform: translateY(0);
}

.modal-header {
  padding: 1.5rem 1.5rem 0 1.5rem;
  border-bottom: 1px dotted #e2e8f0;
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0;
  color: #1a202c;
}

.modal-body {
  padding: 1.5rem;
  
  .deploy-info {
    margin: 0 0 1rem 0;
    color: #4a5568;
    font-weight: 500;
  }
  
  .asset-list {
    background: #f7fafc;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    padding: 0.75rem;
    margin-bottom: 1.5rem;
    max-height: 120px;
    overflow-y: auto;
  }
  
  .asset-item {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
    
    &:last-child {
      margin-bottom: 0;
    }
    
    .asset-tag {
      font-weight: 600;
      color: #2d3748;
    }
    
    .asset-title {
      color: #4a5568;
    }
  }
}

.form-group {
  margin-bottom: 1rem;
}

.form-label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: #2d3748;
  font-size: 0.875rem;
}

.form-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 0.875rem;
  
  &:focus {
    outline: none;
    border-color: #3182ce;
    box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
  }
}

.error-message {
  display: block;
  color: #e53e3e;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.modal-footer {
  padding: 0 1.5rem 1.5rem 1.5rem;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s ease;
  
  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
  
  &--secondary {
    background-color: #e2e8f0;
    color: #4a5568;
    
    &:hover:not(:disabled) {
      background-color: #cbd5e0;
    }
  }
  
  &--primary {
    background-color: #17a2b8;
    color: white;
    
    &:hover:not(:disabled) {
      background-color: #138496;
    }
  }
}
</style>