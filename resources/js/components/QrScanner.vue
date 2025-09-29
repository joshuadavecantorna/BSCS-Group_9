<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { QrcodeStream } from 'vue-qrcode-reader';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Camera, RotateCw, X, CheckCircle, AlertCircle, QrCode, Upload } from 'lucide-vue-next';

// Props
const props = defineProps<{
  show: boolean;
}>();

// Emits
const emit = defineEmits<{
  close: [];
  scanSuccess: [student: any];
}>();

// Reactive state
const camera = ref<'auto' | 'front' | 'rear' | 'off'>('off');
const torch = ref(false);
const error = ref<string>('');
const isLoading = ref(false);
const lastScannedStudent = ref<any>(null);
const cameraStatus = ref<'loading' | 'ready' | 'error' | 'unsupported'>('loading');
const debugInfo = ref<string>('');
const manualQrInput = ref<string>('');
const activeTab = ref<'camera' | 'manual'>('camera');

// Check if we're in a secure context
const isSecureContext = computed(() => window.isSecureContext);

// Debug info
onMounted(() => {
  debugInfo.value = `Secure: ${window.isSecureContext}, Protocol: ${window.location.protocol}, Host: ${window.location.hostname}`;
  console.log('Camera Debug Info:', debugInfo.value);
  
  // Auto-switch to manual mode if not secure
  if (!isSecureContext.value) {
    activeTab.value = 'manual';
    error.value = 'Camera requires HTTPS. Using manual input mode.';
  }
});

// Watch for show prop changes to control camera
watch(() => props.show, (newVal) => {
  if (newVal && activeTab.value === 'camera' && isSecureContext.value) {
    camera.value = 'auto';
    error.value = '';
    cameraStatus.value = 'loading';
  } else {
    camera.value = 'off';
  }
}, { immediate: true });

// Camera initialized successfully
const onCameraOn = () => {
  console.log('Camera initialized successfully');
  cameraStatus.value = 'ready';
  error.value = '';
};

// Camera initialization failed
const onCameraOff = () => {
  console.log('Camera turned off');
  cameraStatus.value = 'loading';
};

// Camera error handling
const onError = (err: any) => {
  console.error('Camera error details:', err);
  cameraStatus.value = 'error';
  
  if (err?.name === 'NotAllowedError') {
    error.value = 'Camera access denied. Please allow camera permissions.';
  } else if (err?.name === 'NotFoundError') {
    error.value = 'No camera found on this device.';
  } else if (err?.name === 'NotSupportedError' || err?.message?.includes('secure context')) {
    error.value = 'Camera not supported in HTTP context. Switch to manual mode.';
    cameraStatus.value = 'unsupported';
  } else {
    error.value = `Camera error: ${err?.message || 'Unknown error'}`;
  }
};

// Scan detection
const onDetect = async (detectedCodes: any[]) => {
  if (isLoading.value || !detectedCodes.length) return;

  const detectedCode = detectedCodes[0];
  const rawValue = detectedCode?.rawValue;

  try {
    isLoading.value = true;
    error.value = '';

    let studentData;
    if (rawValue) {
      try {
        studentData = JSON.parse(rawValue);
        console.log('QR Code parsed successfully:', studentData);
      } catch (parseErr) {
        console.error('QR parse error:', parseErr);
        studentData = generateMockStudent();
        studentData.raw_data = rawValue;
      }
    } else {
      studentData = generateMockStudent();
    }

    await new Promise(resolve => setTimeout(resolve, 1000));
    lastScannedStudent.value = studentData;
    emit('scanSuccess', studentData);
    error.value = '';

  } catch (err) {
    console.error('QR Scan error:', err);
    error.value = 'Scan processing error';
  } finally {
    isLoading.value = false;
  }
};

type MockStudent = {
  id: string;
  student_id: string;
  name: string;
  year: string;
  course: string;
  section: string;
  avatar: null;
  raw_data?: string;
};

const generateMockStudent = (): MockStudent => ({
  id: 'dev-' + Date.now(),
  student_id: 'STU-' + Math.random().toString(36).substr(2, 9).toUpperCase(),
  name: 'Demo Student ' + Math.floor(Math.random() * 100),
  year: '2024',
  course: 'BSCS',
  section: String.fromCharCode(65 + Math.floor(Math.random() * 3)),
  avatar: null
});

// Manual QR processing
const processManualInput = async () => {
  if (!manualQrInput.value.trim()) {
    error.value = 'Please enter QR code data';
    return;
  }

  try {
    isLoading.value = true;
    error.value = '';

    let studentData;
    try {
      studentData = JSON.parse(manualQrInput.value);
      console.log('Manual QR Code parsed successfully:', studentData);
    } catch (parseErr) {
      error.value = 'Invalid JSON format. Please check your QR code data.';
      return;
    }

    await new Promise(resolve => setTimeout(resolve, 1000));
    lastScannedStudent.value = studentData;
    emit('scanSuccess', studentData);
    manualQrInput.value = '';
    error.value = '';

  } catch (err) {
    console.error('Manual scan error:', err);
    error.value = 'Processing error';
  } finally {
    isLoading.value = false;
  }
};

// Quick test scan
const quickTestScan = () => {
  const studentData = generateMockStudent();
  lastScannedStudent.value = studentData;
  emit('scanSuccess', studentData);
};

// Camera switch
const switchCamera = () => {
  if (camera.value === 'auto' || camera.value === 'front') {
    camera.value = 'rear';
  } else {
    camera.value = 'front';
  }
};

// Torch toggle
const toggleTorch = () => {
  torch.value = !torch.value;
};

// Close scanner
const closeScanner = () => {
  camera.value = 'off';
  emit('close');
};

// Switch tabs
const switchToCamera = () => {
  if (!isSecureContext.value) {
    error.value = 'Camera requires HTTPS. Please use manual mode or enable HTTPS in Herd.';
    return;
  }
  activeTab.value = 'camera';
  camera.value = 'auto';
};

const switchToManual = () => {
  activeTab.value = 'manual';
  camera.value = 'off';
};

// Get camera label
const getCameraLabel = () => {
  switch (camera.value) {
    case 'front': return 'Front Camera';
    case 'rear': return 'Rear Camera';
    case 'off': return 'Camera Off';
    default: return 'Auto Camera';
  }
};

// Get initials for avatar
const getInitials = (name: string) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase();
};
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 bg-background flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between p-4 border-b bg-background/95 backdrop-blur">
      <div>
        <h2 class="text-xl font-bold">QR Code Scanner</h2>
        <p class="text-sm text-muted-foreground">
          {{ activeTab === 'camera' ? 'Camera Mode' : 'Manual Input Mode' }}
        </p>
      </div>
      <div class="flex items-center gap-2">
        <Button variant="outline" size="sm" @click="quickTestScan" class="text-xs">
          Quick Test
        </Button>
        <Button variant="ghost" size="icon" @click="closeScanner">
          <X class="h-5 w-5" />
        </Button>
      </div>
    </div>

    <!-- Mode Tabs -->
    <div class="border-b">
      <div class="flex">
        <button
          @click="switchToCamera"
          :class="[
            'flex-1 py-3 text-sm font-medium border-b-2 transition-colors',
            activeTab === 'camera' 
              ? 'border-primary text-primary' 
              : 'border-transparent text-muted-foreground hover:text-foreground'
          ]"
        >
          <Camera class="h-4 w-4 inline mr-2" />
          Camera Scan
        </button>
        <button
          @click="switchToManual"
          :class="[
            'flex-1 py-3 text-sm font-medium border-b-2 transition-colors',
            activeTab === 'manual' 
              ? 'border-primary text-primary' 
              : 'border-transparent text-muted-foreground hover:text-foreground'
          ]"
        >
          <Upload class="h-4 w-4 inline mr-2" />
          Manual Input
        </button>
      </div>
    </div>

    <!-- Camera Scanner -->
    <div v-if="activeTab === 'camera'" class="flex-1 relative bg-black min-h-0">
      <QrcodeStream
        v-if="camera !== 'off' && isSecureContext"
        :camera="camera"
        :torch="torch"
        @detect="onDetect"
        @error="onError"
        @camera-on="onCameraOn"
        @camera-off="onCameraOff"
        class="absolute inset-0 w-full h-full"
      >
        <!-- Scanning Frame Overlay -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
          <div class="relative">
            <div class="w-64 h-64 border-2 border-primary rounded-lg relative">
              <div class="absolute -top-1 -left-1 w-8 h-8 border-t-4 border-l-4 border-primary rounded-tl"></div>
              <div class="absolute -top-1 -right-1 w-8 h-8 border-t-4 border-r-4 border-primary rounded-tr"></div>
              <div class="absolute -bottom-1 -left-1 w-8 h-8 border-b-4 border-l-4 border-primary rounded-bl"></div>
              <div class="absolute -bottom-1 -right-1 w-8 h-8 border-b-4 border-r-4 border-primary rounded-br"></div>
              <div class="absolute top-0 left-0 right-0 h-0.5 bg-primary shadow-[0_0_10px_rgba(var(--primary),0.5)] animate-scan"></div>
            </div>
            <div class="text-center mt-4 text-white bg-black/70 rounded-lg p-3 backdrop-blur-sm">
              <p class="text-sm font-medium">Position QR code within frame</p>
              <p class="text-xs opacity-70">Scanner works continuously</p>
            </div>
          </div>
        </div>
      </QrcodeStream>

      <!-- Camera States -->
      <div v-if="cameraStatus !== 'ready'" class="absolute inset-0 flex items-center justify-center bg-gray-900">
        <div class="text-center text-white p-6 max-w-sm">
          <div v-if="cameraStatus === 'loading'" class="space-y-3">
            <RotateCw class="h-12 w-12 animate-spin mx-auto text-blue-400" />
            <p class="font-medium">Initializing Camera...</p>
          </div>
          <div v-else-if="cameraStatus === 'unsupported'" class="space-y-4">
            <AlertCircle class="h-12 w-12 mx-auto text-yellow-400" />
            <p class="font-medium">Camera Not Available</p>
            <p class="text-sm opacity-70">Camera requires HTTPS. Switch to manual mode or enable HTTPS in Herd.</p>
            <Button @click="switchToManual" class="w-full">
              Switch to Manual Mode
            </Button>
          </div>
          <div v-else-if="cameraStatus === 'error'" class="space-y-4">
            <AlertCircle class="h-12 w-12 mx-auto text-red-400" />
            <p class="font-medium">Camera Error</p>
            <p class="text-sm opacity-70">{{ error }}</p>
            <Button @click="camera = 'auto'" variant="outline" class="w-full">
              Retry Camera
            </Button>
          </div>
        </div>
      </div>

      <!-- Camera Controls -->
      <div v-if="cameraStatus === 'ready'" class="absolute bottom-20 left-1/2 transform -translate-x-1/2 flex gap-3">
        <Button 
          variant="secondary" 
          size="icon" 
          @click="switchCamera" 
          :title="getCameraLabel()"
          class="h-12 w-12 rounded-full shadow-lg bg-white/20 backdrop-blur text-white border-white/30"
        >
          <Camera class="h-5 w-5" />
        </Button>
        <Button 
          variant="secondary" 
          size="icon" 
          @click="toggleTorch" 
          title="Toggle flashlight"
          class="h-12 w-12 rounded-full shadow-lg bg-white/20 backdrop-blur text-white border-white/30"
        >
          <div class="w-5 h-5 rounded-full border-2" :class="torch ? 'bg-yellow-400 border-yellow-500' : 'bg-gray-400 border-gray-500'"></div>
        </Button>
      </div>
    </div>

    <!-- Manual Input -->
    <div v-else class="flex-1 p-6 bg-gray-50">
      <div class="max-w-md mx-auto space-y-6">
        <div class="text-center">
          <QrCode class="h-16 w-16 mx-auto text-gray-400 mb-4" />
          <h3 class="text-lg font-semibold mb-2">Manual QR Input</h3>
          <p class="text-sm text-gray-600">Paste QR code JSON data or use Quick Test</p>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              QR Code JSON Data
            </label>
            <textarea
              v-model="manualQrInput"
              placeholder='{"student_id": "STU-001", "name": "John Doe", "year": "2024", "course": "BSCS", "section": "A"}'
              class="w-full p-3 border border-gray-300 rounded-lg text-sm resize-none h-32 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :disabled="isLoading"
            ></textarea>
            <p class="text-xs text-gray-500 mt-1">
              Paste the JSON data from a QR code scan
            </p>
          </div>

          <Button 
            @click="processManualInput" 
            :disabled="isLoading || !manualQrInput.trim()" 
            class="w-full"
          >
            <CheckCircle class="mr-2 h-4 w-4" />
            {{ isLoading ? 'Processing...' : 'Mark Attendance' }}
          </Button>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="isLoading" class="absolute inset-0 bg-black/70 flex items-center justify-center backdrop-blur-sm z-10">
      <div class="text-white text-center bg-black/50 rounded-lg p-6">
        <RotateCw class="h-10 w-10 animate-spin mx-auto mb-3" />
        <p class="font-medium">Processing QR code...</p>
      </div>
    </div>

    <!-- Error Alert -->
    <Alert v-if="error" variant="destructive" class="m-4">
      <AlertCircle class="h-4 w-4" />
      <AlertDescription>{{ error }}</AlertDescription>
    </Alert>

    <!-- Last Scanned Student -->
    <div class="border-t p-4 space-y-4 max-h-64 overflow-y-auto bg-white">
      <div v-if="lastScannedStudent" class="space-y-2">
        <h3 class="font-semibold text-sm">Last Scanned</h3>
        <Card>
          <CardContent class="p-3">
            <div class="flex items-center space-x-3">
              <Avatar class="h-10 w-10">
                <AvatarFallback>{{ getInitials(lastScannedStudent.name) }}</AvatarFallback>
              </Avatar>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-medium truncate">{{ lastScannedStudent.name }}</p>
                  <Badge variant="default" class="text-xs bg-green-500 text-white">
                    <CheckCircle class="h-3 w-3 mr-1" />
                    Present
                  </Badge>
                </div>
                <p class="text-xs text-muted-foreground truncate">
                  {{ lastScannedStudent.course }} • {{ lastScannedStudent.year }}-{{ lastScannedStudent.section }}
                </p>
                <p class="text-xs text-muted-foreground">ID: {{ lastScannedStudent.student_id }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div v-else class="text-center text-muted-foreground py-4">
        <QrCode class="h-8 w-8 mx-auto mb-2 opacity-50" />
        <p class="text-sm">
          {{ activeTab === 'camera' ? 'Scan a QR code to mark attendance' : 'Enter QR data above to mark attendance' }}
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
:deep(.qrcode-stream-camera) {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
}

@keyframes scan {
  0% { top: 0; }
  50% { top: 100%; }
  100% { top: 0; }
}

.animate-scan {
  animation: scan 2s ease-in-out infinite;
}
</style>