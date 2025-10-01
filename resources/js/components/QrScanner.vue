<script setup lang="ts">
import { ref, watch, onMounted, computed, nextTick } from 'vue';
import { QrcodeStream } from 'vue-qrcode-reader';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { 
  Camera, 
  RotateCw, 
  X, 
  CheckCircle, 
  AlertCircle, 
  QrCode, 
  Upload, 
  Zap, 
  SwitchCamera, 
  Flashlight,
  Scan,
  User,
  Shield
} from 'lucide-vue-next';

// Types
interface Student {
  id: string;
  student_id: string;
  name: string;
  year: string;
  course: string;
  section: string;
  avatar?: string | null;
  raw_data?: string;
}

interface ScannerState {
  camera: 'auto' | 'front' | 'rear' | 'off';
  torch: boolean;
  error: string;
  isLoading: boolean;
  lastScannedStudent: Student | null;
  cameraStatus: 'loading' | 'ready' | 'error' | 'unsupported' | 'denied';
  debugInfo: string;
  manualQrInput: string;
  activeTab: 'camera' | 'manual';
  scanHistory: Student[];
  isPaused: boolean;
}

// Props
const props = defineProps<{
  show: boolean;
  autoCloseOnSuccess?: boolean;
  maxScanHistory?: number;
}>();

// Emits
const emit = defineEmits<{
  close: [];
  scanSuccess: [student: Student];
  scanError: [error: string];
  cameraStateChange: [state: string];
}>();

// Reactive state
const state = ref<ScannerState>({
  camera: 'off',
  torch: false,
  error: '',
  isLoading: false,
  lastScannedStudent: null,
  cameraStatus: 'loading',
  debugInfo: '',
  manualQrInput: '',
  activeTab: 'camera',
  scanHistory: [],
  isPaused: false
});

// Computed properties
const isSecureContext = computed(() => window.isSecureContext);
const hasCameraSupport = computed(() => {
  return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
});
const canToggleTorch = computed(() => {
  return state.value.cameraStatus === 'ready' && state.value.camera !== 'off';
});
const scanCount = computed(() => state.value.scanHistory.length);
const recentScans = computed(() => state.value.scanHistory.slice(-3));

// Debug info and initialization
onMounted(() => {
  state.value.debugInfo = `Secure: ${window.isSecureContext}, Protocol: ${window.location.protocol}, Host: ${window.location.hostname}, Camera API: ${hasCameraSupport.value}`;
  console.log('QR Scanner Debug Info:', state.value.debugInfo);
  
  checkCameraCapabilities();
});

// Enhanced camera capability check
const checkCameraCapabilities = async () => {
  if (!hasCameraSupport.value) {
    state.value.cameraStatus = 'unsupported';
    state.value.activeTab = 'manual';
    state.value.error = 'Camera API not supported in this browser';
    return;
  }

  if (!isSecureContext.value) {
    state.value.cameraStatus = 'unsupported';
    state.value.activeTab = 'manual';
    state.value.error = 'Camera requires HTTPS. Using manual input mode.';
    return;
  }

  // Test camera permissions
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    stream.getTracks().forEach(track => track.stop());
    state.value.cameraStatus = 'ready';
  } catch (err: any) {
    console.warn('Camera permission test failed:', err);
    if (err.name === 'NotAllowedError') {
      state.value.cameraStatus = 'denied';
      state.value.error = 'Camera access denied. Please allow camera permissions in your browser settings.';
    } else {
      state.value.cameraStatus = 'error';
      state.value.error = 'Camera initialization failed.';
    }
  }
};

// Watch for show prop changes with debouncing
let cameraTimeout: NodeJS.Timeout;
watch(() => props.show, (newVal) => {
  clearTimeout(cameraTimeout);
  
  if (newVal && state.value.activeTab === 'camera' && isSecureContext.value) {
    state.value.camera = 'auto';
    state.value.error = '';
    state.value.cameraStatus = 'loading';
    
    // Small delay to ensure DOM is ready
    cameraTimeout = setTimeout(() => {
      if (state.value.cameraStatus === 'loading') {
        state.value.cameraStatus = 'ready';
      }
    }, 1000);
  } else {
    state.value.camera = 'off';
  }
}, { immediate: true });

// Camera event handlers
const onCameraOn = () => {
  console.log('Camera initialized successfully');
  state.value.cameraStatus = 'ready';
  state.value.error = '';
  emit('cameraStateChange', 'ready');
};

const onCameraOff = () => {
  console.log('Camera turned off');
  state.value.cameraStatus = 'loading';
  emit('cameraStateChange', 'off');
};

// Enhanced error handling
const onError = (err: any) => {
  console.error('Camera error details:', err);
  
  if (err?.name === 'NotAllowedError') {
    state.value.cameraStatus = 'denied';
    state.value.error = 'Camera access denied. Please allow camera permissions in your browser settings.';
  } else if (err?.name === 'NotFoundError') {
    state.value.cameraStatus = 'error';
    state.value.error = 'No camera found on this device.';
  } else if (err?.name === 'NotSupportedError' || err?.message?.includes('secure context')) {
    state.value.cameraStatus = 'unsupported';
    state.value.error = 'Camera not supported. Switch to manual mode.';
    state.value.activeTab = 'manual';
  } else if (err?.name === 'OverconstrainedError') {
    state.value.cameraStatus = 'error';
    state.value.error = 'Camera constraints could not be satisfied. Try switching cameras.';
  } else {
    state.value.cameraStatus = 'error';
    state.value.error = `Camera error: ${err?.message || 'Unknown error'}`;
  }
  
  emit('cameraStateChange', 'error');
  emit('scanError', state.value.error);
};

// Debounced scan detection
let scanCooldown = false;
const SCAN_COOLDOWN_MS = 1000;

const onDetect = async (detectedCodes: any[]) => {
  if (state.value.isLoading || !detectedCodes.length || scanCooldown || state.value.isPaused) return;
  
  scanCooldown = true;
  setTimeout(() => { scanCooldown = false; }, SCAN_COOLDOWN_MS);

  const detectedCode = detectedCodes[0];
  const rawValue = detectedCode?.rawValue;

  try {
    state.value.isLoading = true;
    state.value.error = '';

    // Pause camera during processing
    state.value.isPaused = true;

    const studentData = await processQrData(rawValue);
    
    // Add to scan history
    addToScanHistory(studentData);
    
    state.value.lastScannedStudent = studentData;
    emit('scanSuccess', studentData);
    
    if (props.autoCloseOnSuccess) {
      setTimeout(() => closeScanner(), 1500);
    }
    
  } catch (err) {
    console.error('QR Scan error:', err);
    state.value.error = 'Failed to process QR code';
    emit('scanError', state.value.error);
  } finally {
    state.value.isLoading = false;
    // Resume camera after a brief delay
    setTimeout(() => { state.value.isPaused = false; }, 500);
  }
};

// Process QR data with validation
const processQrData = async (rawValue: string): Promise<Student> => {
  if (!rawValue) {
    return generateMockStudent();
  }

  try {
    const studentData = JSON.parse(rawValue);
    
    // Validate student data structure
    if (!isValidStudentData(studentData)) {
      throw new Error('Invalid student data structure');
    }
    
    console.log('QR Code parsed successfully:', studentData);
    return studentData;
  } catch (parseErr) {
    console.error('QR parse error:', parseErr);
    
    // Try to extract basic info from raw string
    const fallbackStudent = generateMockStudent();
    fallbackStudent.raw_data = rawValue;
    fallbackStudent.name = `Scanned: ${rawValue.substring(0, 20)}...`;
    
    return fallbackStudent;
  }
};

// Student data validation
const isValidStudentData = (data: any): data is Student => {
  return (
    data &&
    typeof data.student_id === 'string' &&
    typeof data.name === 'string' &&
    typeof data.course === 'string'
  );
};

// Scan history management
const addToScanHistory = (student: Student) => {
  state.value.scanHistory = [
    ...state.value.scanHistory,
    { ...student, id: `${student.id}-${Date.now()}` }
  ].slice(-(props.maxScanHistory || 10));
};

const generateMockStudent = (): Student => ({
  id: 'dev-' + Date.now(),
  student_id: 'STU-' + Math.random().toString(36).substr(2, 9).toUpperCase(),
  name: 'Demo Student ' + Math.floor(Math.random() * 100),
  year: '2024',
  course: 'BSCS',
  section: String.fromCharCode(65 + Math.floor(Math.random() * 3)),
  avatar: null
});

// Manual QR processing with validation
const processManualInput = async () => {
  if (!state.value.manualQrInput.trim()) {
    state.value.error = 'Please enter QR code data';
    return;
  }

  try {
    state.value.isLoading = true;
    state.value.error = '';

    const studentData = await processQrData(state.value.manualQrInput);
    
    addToScanHistory(studentData);
    state.value.lastScannedStudent = studentData;
    emit('scanSuccess', studentData);
    
    state.value.manualQrInput = '';
    state.value.error = '';

    if (props.autoCloseOnSuccess) {
      setTimeout(() => closeScanner(), 1500);
    }
    
  } catch (err) {
    console.error('Manual scan error:', err);
    state.value.error = 'Failed to process QR code data';
    emit('scanError', state.value.error);
  } finally {
    state.value.isLoading = false;
  }
};

// Quick test scan
const quickTestScan = () => {
  const studentData = generateMockStudent();
  addToScanHistory(studentData);
  state.value.lastScannedStudent = studentData;
  emit('scanSuccess', studentData);
};

// Camera controls
const switchCamera = () => {
  if (state.value.camera === 'auto' || state.value.camera === 'front') {
    state.value.camera = 'rear';
  } else {
    state.value.camera = 'front';
  }
};

const toggleTorch = () => {
  if (canToggleTorch.value) {
    state.value.torch = !state.value.torch;
  }
};

// Scanner controls
const pauseScanner = () => {
  state.value.isPaused = true;
};

const resumeScanner = () => {
  state.value.isPaused = false;
};

// Close scanner with cleanup
const closeScanner = () => {
  state.value.camera = 'off';
  state.value.isPaused = false;
  emit('close');
};

// Tab switching
const switchToCamera = () => {
  if (!isSecureContext.value || !hasCameraSupport.value) {
    state.value.error = 'Camera not available. Please use manual mode.';
    return;
  }
  state.value.activeTab = 'camera';
  state.value.camera = 'auto';
  state.value.error = '';
};

const switchToManual = () => {
  state.value.activeTab = 'manual';
  state.value.camera = 'off';
  state.value.error = '';
};

// Utility functions
const getCameraLabel = () => {
  switch (state.value.camera) {
    case 'front': return 'Front Camera';
    case 'rear': return 'Rear Camera';
    case 'off': return 'Camera Off';
    default: return 'Auto Camera';
  }
};

const getInitials = (name: string) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

const clearError = () => {
  state.value.error = '';
};

const clearScanHistory = () => {
  state.value.scanHistory = [];
};
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 bg-background/95 backdrop-blur-sm">
    <div class="flex flex-col h-full">
      <!-- Enhanced Header -->
      <div class="flex-shrink-0 border-b bg-card/50 backdrop-blur">
        <div class="container mx-auto px-4 py-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                <Scan class="h-6 w-6 text-primary-foreground" />
              </div>
              <div>
                <h2 class="text-2xl font-bold tracking-tight flex items-center gap-2">
                  QR Scanner
                  <Badge variant="secondary" class="text-xs">
                    {{ scanCount }} scans
                  </Badge>
                </h2>
                <p class="text-sm text-muted-foreground">
                  Scan student ID cards to mark attendance
                </p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="outline" size="sm" @click="quickTestScan" :disabled="state.isLoading">
                <Zap class="h-4 w-4 mr-2" />
                Test Scan
              </Button>
              <Button variant="ghost" size="icon" @click="closeScanner" :disabled="state.isLoading">
                <X class="h-5 w-5" />
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="flex-1 overflow-hidden">
        <Tabs :value="state.activeTab" class="h-full flex flex-col">
          <!-- Enhanced Tab Header -->
          <div class="flex-shrink-0 border-b bg-muted/20">
            <div class="container mx-auto px-4">
              <div class="flex items-center justify-between py-3">
                <TabsList class="grid grid-cols-2 bg-transparent h-auto p-0 gap-1">
                  <TabsTrigger 
                    value="camera" 
                    @click="switchToCamera"
                    class="rounded-lg data-[state=active]:bg-background data-[state=active]:shadow-sm"
                    :disabled="!isSecureContext || !hasCameraSupport"
                  >
                    <Camera class="h-4 w-4 mr-2" />
                    Camera Scan
                    <Shield v-if="!isSecureContext" class="h-3 w-3 ml-1 text-yellow-500" />
                  </TabsTrigger>
                  <TabsTrigger 
                    value="manual" 
                    @click="switchToManual"
                    class="rounded-lg data-[state=active]:bg-background data-[state=active]:shadow-sm"
                  >
                    <Upload class="h-4 w-4 mr-2" />
                    Manual Input
                  </TabsTrigger>
                </TabsList>
                
                <!-- Scanner Status -->
                <div class="flex items-center gap-2 text-sm">
                  <div v-if="state.activeTab === 'camera'" class="flex items-center gap-2">
                    <div class="flex items-center gap-1">
                      <div 
                        :class="[
                          'w-2 h-2 rounded-full',
                          state.cameraStatus === 'ready' ? 'bg-green-500' :
                          state.cameraStatus === 'loading' ? 'bg-yellow-500' :
                          'bg-red-500'
                        ]" 
                      />
                      <span class="text-xs text-muted-foreground capitalize">
                        {{ state.cameraStatus }}
                      </span>
                    </div>
                    <Badge v-if="state.isPaused" variant="outline" class="text-xs">
                      Paused
                    </Badge>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Camera Tab Content -->
          <TabsContent value="camera" class="flex-1 m-0 relative bg-black data-[state=inactive]:hidden">
            <!-- QR Scanner Stream -->
            <QrcodeStream
              v-if="state.camera !== 'off' && isSecureContext && hasCameraSupport"
              :camera="state.camera"
              :torch="state.torch"
              :paused="state.isPaused"
              @detect="onDetect"
              @error="onError"
              @camera-on="onCameraOn"
              @camera-off="onCameraOff"
              class="absolute inset-0 w-full h-full"
            >
              <!-- Enhanced Scanning Overlay -->
              <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="relative">
                  <!-- Scanner Frame with Improved Design -->
                  <div class="w-80 h-80 relative">
                    <!-- Animated Corner Brackets -->
                    <div class="absolute -top-1 -left-1 w-16 h-16">
                      <div class="w-6 h-6 border-t-2 border-l-2 border-primary rounded-tl-lg animate-pulse"></div>
                    </div>
                    <div class="absolute -top-1 -right-1 w-16 h-16">
                      <div class="w-6 h-6 border-t-2 border-r-2 border-primary rounded-tr-lg animate-pulse"></div>
                    </div>
                    <div class="absolute -bottom-1 -left-1 w-16 h-16">
                      <div class="w-6 h-6 border-b-2 border-l-2 border-primary rounded-bl-lg animate-pulse"></div>
                    </div>
                    <div class="absolute -bottom-1 -right-1 w-16 h-16">
                      <div class="w-6 h-6 border-b-2 border-r-2 border-primary rounded-br-lg animate-pulse"></div>
                    </div>
                    
                    <!-- Enhanced Scanning Line -->
                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-transparent via-primary to-transparent shadow-lg animate-scan"></div>
                  </div>
                  
                  <!-- Instructions -->
                  <div class="text-center mt-8">
                    <Card class="bg-background/95 backdrop-blur border-primary/20 shadow-lg">
                      <CardContent class="p-4">
                        <p class="text-sm font-medium mb-1 flex items-center justify-center gap-2">
                          <Scan class="h-4 w-4 text-primary" />
                          Position QR code within frame
                        </p>
                        <p class="text-xs text-muted-foreground">Scanner will automatically detect the code</p>
                      </CardContent>
                    </Card>
                  </div>
                </div>
              </div>
            </QrcodeStream>

            <!-- Camera States -->
            <div v-if="state.cameraStatus !== 'ready'" class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-muted/50 to-background">
              <Card class="max-w-md w-full mx-4">
                <CardContent class="p-6 text-center space-y-4">
                  <!-- Loading State -->
                  <div v-if="state.cameraStatus === 'loading'">
                    <RotateCw class="h-12 w-12 animate-spin mx-auto text-primary mb-4" />
                    <div>
                      <p class="font-semibold text-lg mb-2">Initializing Camera</p>
                      <p class="text-sm text-muted-foreground">Please wait while we set up your camera...</p>
                    </div>
                  </div>
                  
                  <!-- Unsupported State -->
                  <div v-else-if="state.cameraStatus === 'unsupported'">
                    <AlertCircle class="h-12 w-12 mx-auto text-yellow-500 mb-4" />
                    <div>
                      <p class="font-semibold text-lg mb-2">Camera Not Available</p>
                      <p class="text-sm text-muted-foreground mb-4">
                        Camera requires HTTPS connection or is not supported in your browser.
                      </p>
                    </div>
                    <Button @click="switchToManual" class="w-full">
                      <Upload class="h-4 w-4 mr-2" />
                      Switch to Manual Mode
                    </Button>
                  </div>
                  
                  <!-- Permission Denied -->
                  <div v-else-if="state.cameraStatus === 'denied'">
                    <Shield class="h-12 w-12 mx-auto text-red-500 mb-4" />
                    <div>
                      <p class="font-semibold text-lg mb-2">Camera Permission Denied</p>
                      <p class="text-sm text-muted-foreground mb-4">
                        Please allow camera access in your browser settings to use the scanner.
                      </p>
                    </div>
                    <div class="space-y-2">
                      <Button @click="switchToManual" class="w-full">
                        Use Manual Mode
                      </Button>
                      <Button @click="checkCameraCapabilities" variant="outline" class="w-full">
                        Retry Camera
                      </Button>
                    </div>
                  </div>
                  
                  <!-- Error State -->
                  <div v-else-if="state.cameraStatus === 'error'">
                    <AlertCircle class="h-12 w-12 mx-auto text-destructive mb-4" />
                    <div>
                      <p class="font-semibold text-lg mb-2">Camera Error</p>
                      <p class="text-sm text-muted-foreground mb-4">{{ state.error }}</p>
                    </div>
                    <div class="space-y-2">
                      <Button @click="state.camera = 'auto'" variant="outline" class="w-full">
                        <RotateCw class="h-4 w-4 mr-2" />
                        Retry Camera
                      </Button>
                      <Button @click="switchToManual" class="w-full">
                        Use Manual Mode
                      </Button>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>

            <!-- Enhanced Camera Controls -->
            <div v-if="state.cameraStatus === 'ready'" class="absolute bottom-6 left-1/2 transform -translate-x-1/2 flex gap-3">
              <Button 
                variant="secondary" 
                size="lg" 
                @click="switchCamera" 
                :title="getCameraLabel()"
                class="rounded-full shadow-xl backdrop-blur bg-background/80"
              >
                <SwitchCamera class="h-5 w-5 mr-2" />
                Switch
              </Button>
              
              <Button 
                :variant="state.torch ? 'default' : 'secondary'"
                size="lg" 
                @click="toggleTorch" 
                :disabled="!canToggleTorch"
                :title="state.torch ? 'Turn off flash' : 'Turn on flash'"
                class="rounded-full shadow-xl backdrop-blur bg-background/80"
              >
                <Flashlight class="h-5 w-5 mr-2" />
                {{ state.torch ? 'On' : 'Off' }}
              </Button>
              
              <Button 
                :variant="state.isPaused ? 'destructive' : 'secondary'"
                size="lg" 
                @click="state.isPaused ? resumeScanner() : pauseScanner()"
                :title="state.isPaused ? 'Resume scanning' : 'Pause scanning'"
                class="rounded-full shadow-xl backdrop-blur bg-background/80"
              >
                <Camera class="h-5 w-5 mr-2" />
                {{ state.isPaused ? 'Resume' : 'Pause' }}
              </Button>
            </div>
          </TabsContent>

          <!-- Enhanced Manual Input Tab -->
          <TabsContent value="manual" class="flex-1 m-0 p-6 overflow-auto data-[state=inactive]:hidden">
            <div class="container mx-auto max-w-2xl">
              <div class="grid gap-6">
                <!-- Manual Input Card -->
                <Card>
                  <CardHeader class="text-center pb-4">
                    <div class="mx-auto mb-4 w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center">
                      <QrCode class="h-10 w-10 text-primary" />
                    </div>
                    <CardTitle>Manual QR Code Input</CardTitle>
                    <p class="text-sm text-muted-foreground">
                      Paste JSON data from QR code or use test scan
                    </p>
                  </CardHeader>
                  <CardContent class="space-y-4">
                    <div class="space-y-3">
                      <Label for="qr-data" class="flex items-center gap-2">
                        <Upload class="h-4 w-4" />
                        QR Code JSON Data
                      </Label>
                      <Textarea
                        id="qr-data"
                        v-model="state.manualQrInput"
                        placeholder='{"student_id": "STU-001", "name": "John Doe", "year": "2024", "course": "BSCS", "section": "A"}'
                        class="min-h-[140px] font-mono text-sm resize-none"
                        :disabled="state.isLoading"
                      />
                      <div class="flex items-center justify-between text-xs text-muted-foreground">
                        <span>Enter valid JSON format</span>
                        <span>{{ state.manualQrInput.length }} characters</span>
                      </div>
                    </div>

                    <Button 
                      @click="processManualInput" 
                      :disabled="state.isLoading || !state.manualQrInput.trim()" 
                      class="w-full"
                      size="lg"
                    >
                      <CheckCircle class="mr-2 h-5 w-5" />
                      {{ state.isLoading ? 'Processing...' : 'Mark Attendance' }}
                    </Button>
                  </CardContent>
                </Card>

                <!-- Recent Scans -->
                <Card v-if="recentScans.length > 0">
                  <CardHeader class="pb-3">
                    <CardTitle class="text-lg flex items-center gap-2">
                      <User class="h-5 w-5" />
                      Recent Scans ({{ recentScans.length }})
                    </CardTitle>
                  </CardHeader>
                  <CardContent class="space-y-3">
                    <div 
                      v-for="student in recentScans" 
                      :key="student.id"
                      class="flex items-center gap-3 p-3 rounded-lg border bg-muted/20 hover:bg-muted/40 transition-colors"
                    >
                      <Avatar class="h-10 w-10 border">
                        <AvatarFallback class="text-xs font-semibold">
                          {{ getInitials(student.name) }}
                        </AvatarFallback>
                      </Avatar>
                      <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm truncate">{{ student.name }}</p>
                        <p class="text-xs text-muted-foreground truncate">
                          {{ student.course }} • {{ student.student_id }}
                        </p>
                      </div>
                      <Badge variant="outline" class="text-xs">
                        Present
                      </Badge>
                    </div>
                  </CardContent>
                </Card>
              </div>
            </div>
          </TabsContent>
        </Tabs>
      </div>

      <!-- Enhanced Loading Overlay -->
      <div v-if="state.isLoading" class="absolute inset-0 bg-background/90 backdrop-blur-sm flex items-center justify-center z-20">
        <Card class="max-w-sm animate-pulse">
          <CardContent class="p-6 text-center space-y-4">
            <div class="relative">
              <RotateCw class="h-12 w-12 animate-spin mx-auto text-primary" />
              <Scan class="h-6 w-6 text-primary absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2" />
            </div>
            <div>
              <p class="font-semibold text-lg mb-1">Processing QR Code</p>
              <p class="text-sm text-muted-foreground">Marking attendance...</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Enhanced Error Alert -->
      <div v-if="state.error" class="flex-shrink-0 p-4 border-t bg-destructive/5">
        <div class="container mx-auto max-w-2xl">
          <Alert variant="destructive" class="animate-in slide-in-from-bottom">
            <div class="flex items-center justify-between w-full">
              <div class="flex items-center gap-2">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>{{ state.error }}</AlertDescription>
              </div>
              <Button variant="ghost" size="sm" @click="clearError" class="h-8 w-8 p-0">
                <X class="h-4 w-4" />
              </Button>
            </div>
          </Alert>
        </div>
      </div>

      <!-- Enhanced Last Scanned Section -->
      <div class="flex-shrink-0 border-t bg-gradient-to-r from-muted/30 to-background/30">
        <div class="container mx-auto px-4 py-4">
          <div v-if="state.lastScannedStudent" class="space-y-3">
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-muted-foreground">Last Scanned Student</p>
              <Badge variant="secondary" class="text-xs">
                {{ scanCount }} total
              </Badge>
            </div>
            <Card class="bg-card/50 backdrop-blur border-primary/20 shadow-sm">
              <CardContent class="p-4">
                <div class="flex items-center gap-4">
                  <Avatar class="h-14 w-14 border-2 border-primary/20 shadow-sm">
                    <AvatarFallback class="bg-primary/10 text-primary font-semibold text-sm">
                      {{ getInitials(state.lastScannedStudent.name) }}
                    </AvatarFallback>
                  </Avatar>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-2">
                      <p class="font-semibold text-lg truncate">{{ state.lastScannedStudent.name }}</p>
                      <Badge class="bg-green-500 hover:bg-green-500 shadow-sm">
                        <CheckCircle class="h-3 w-3 mr-1" />
                        Present
                      </Badge>
                    </div>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                      <div>
                        <span class="text-muted-foreground">ID:</span>
                        <span class="font-medium ml-1">{{ state.lastScannedStudent.student_id }}</span>
                      </div>
                      <div>
                        <span class="text-muted-foreground">Course:</span>
                        <span class="font-medium ml-1">{{ state.lastScannedStudent.course }}</span>
                      </div>
                      <div>
                        <span class="text-muted-foreground">Year/Section:</span>
                        <span class="font-medium ml-1">{{ state.lastScannedStudent.year }}-{{ state.lastScannedStudent.section }}</span>
                      </div>
                      <div>
                        <span class="text-muted-foreground">Status:</span>
                        <span class="font-medium ml-1 text-green-600">Marked Present</span>
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <div v-else class="text-center py-8">
            <div class="mx-auto mb-4 w-16 h-16 bg-muted rounded-full flex items-center justify-center">
              <QrCode class="h-8 w-8 text-muted-foreground" />
            </div>
            <p class="text-sm text-muted-foreground">
              {{ state.activeTab === 'camera' ? 'Scan a QR code to mark attendance' : 'Enter QR data above to mark attendance' }}
            </p>
            <Button variant="outline" size="sm" @click="quickTestScan" class="mt-3">
              <Zap class="h-4 w-4 mr-2" />
              Quick Test
            </Button>
          </div>
        </div>
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

:deep(.qrcode-stream-overlay) {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

@keyframes scan {
  0% {
    top: 0;
    opacity: 0;
    transform: scaleX(0.8);
  }
  10% {
    opacity: 1;
  }
  90% {
    opacity: 1;
  }
  100% {
    top: 100%;
    opacity: 0;
    transform: scaleX(1.2);
  }
}

.animate-scan {
  animation: scan 2.5s ease-in-out infinite;
}

/* Smooth transitions for scanner states */
.scanner-transition-enter-active,
.scanner-transition-leave-active {
  transition: all 0.3s ease;
}

.scanner-transition-enter-from,
.scanner-transition-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

/* Backdrop blur support fallback */
@supports not (backdrop-filter: blur(10px)) {
  .backdrop-blur {
    background-color: rgba(255, 255, 255, 0.9);
  }
}
</style>