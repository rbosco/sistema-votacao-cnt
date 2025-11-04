<template>
  <label class="switch" :class="{ disabled: disabled }">
    <input
      type="checkbox"
      :checked="modelValue"
      @change="handleChange"
      :disabled="disabled"
    />
    <span class="slider"></span>
  </label>
</template>

<script setup lang="ts">
interface Props {
  modelValue: boolean
  disabled?: boolean
}

defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
}>()

function handleChange(event: Event) {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.checked)
}
</script>

<style scoped>
.switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 24px;
  cursor: pointer;
}

.switch.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  transition: 0.3s;
  border-radius: 24px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

input:checked + .slider {
  background-color: #2b8a3e;
}

input:checked + .slider:before {
  transform: translateX(26px);
}

input:disabled + .slider {
  cursor: not-allowed;
}
</style>
