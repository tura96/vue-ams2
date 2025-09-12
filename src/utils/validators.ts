export const validateAssetTag = (tag: string): boolean => {
  return /^AST-d{4}$/.test(tag)
}

export const validateEmail = (email: string): boolean => {
  const emailRegex = /^[^s@]+@[^s@]+.[^s@]+$/
  return emailRegex.test(email)
}

export const validateRequired = (value: any): boolean => {
  return value !== null && value !== undefined && value !== ''
}