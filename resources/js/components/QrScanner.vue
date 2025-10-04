<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { QrcodeStream } from 'vue-qrcode-reader';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Camera, RotateCw, X, CheckCircle, AlertCircle, QrCode, Upload, Zap, SwitchCamera, Flashlight, User, BookOpen, Calendar, Hash, GraduationCap } from 'lucide-vue-next';

// Types
interface Student {
  id: string;
  student_id: string;
  name: string;
  year: string; // 1st, 2nd, 3rd, 4th, 5th year
  course: string;
  section: string;
  avatar?: string | null;
  timestamp?: string;
}

interface ScanResult {
  student: Student;
  rawData: string;
  format: 'json' | 'csv' | 'url' | 'keyvalue' | 'mock';
}

// Props
const props = defineProps<{
  show: boolean;
}>();

// Emits
const emit = defineEmits<{
  close: [];
  scanSuccess: [student: Student];
}>();

// Reactive state
const camera = ref<'auto' | 'front' | 'rear' | 'off'>('off');
const torch = ref(false);
const error = ref<string>('');
const isLoading = ref(false);
const lastScannedStudent = ref<Student | null>(null);
const cameraStatus = ref<'loading' | 'ready' | 'error' | 'unsupported'>('loading');
const debugInfo = ref<string>('');
const manualQrInput = ref<string>('');
const activeTab = ref<'camera' | 'manual'>('camera');
const scanHistory = ref<ScanResult[]>([]);

// Manual form fields with validation
const studentId = ref<string>('');
const studentName = ref<string>('');
const studentYear = ref<string>(''); // 1st, 2nd, 3rd, 4th, 5th
const studentCourse = ref<string>('');
const studentSection = ref<string>('');
const formErrors = ref<Record<string, string>>({});

// Year level options
const yearLevels = [
  { value: '1st', label: '1st Year' },
  { value: '2nd', label: '2nd Year' },
  { value: '3rd', label: '3rd Year' },
  { value: '4th', label: '4th Year' },
  { value: '5th', label: '5th Year' }
];

// Form validation rules
const validationRules = {
  student_id: (value: string) => {
    if (!value.trim()) return 'Student ID is required';
    if (value.length < 3) return 'Student ID must be at least 3 characters';
    return '';
  },
  name: (value: string) => {
    if (!value.trim()) return 'Name is required';
    if (value.length < 2) return 'Name must be at least 2 characters';
    if (!/^[a-zA-Z\s.'-]+$/.test(value)) return 'Name can only contain letters, spaces, and basic punctuation';
    return '';
  },
  year: (value: string) => {
    if (value && !/^(1st|2nd|3rd|4th|5th)$/.test(value)) return 'Please select a valid year level';
    return '';
  },
  course: (value: string) => {
    if (value && value.length > 50) return 'Course name is too long';
    return '';
  },
  section: (value: string) => {
    if (value && !/^[A-Za-z0-9]{1,5}$/.test(value)) return 'Section must be 1-5 alphanumeric characters';
    return '';
  }
};

// Validate form field
const validateField = (field: string, value: string) => {
  const validator = validationRules[field as keyof typeof validationRules];
  if (validator) {
    formErrors.value[field] = validator(value);
  }
};

// Validate entire form
const validateForm = (): boolean => {
  const fields = {
    student_id: studentId.value,
    name: studentName.value,
    year: studentYear.value,
    course: studentCourse.value,
    section: studentSection.value
  };

  Object.entries(fields).forEach(([field, value]) => {
    validateField(field, value);
  });

  return !Object.values(formErrors.value).some(error => error);
};

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

  // Load scan history from localStorage
  const savedHistory = localStorage.getItem('qrScanHistory');
  if (savedHistory) {
    try {
      scanHistory.value = JSON.parse(savedHistory).slice(0, 10); // Keep last 10 scans
    } catch (e) {
      console.error('Failed to load scan history:', e);
    }
  }
});

// Save scan to history
const saveToHistory = (result: ScanResult) => {
  scanHistory.value.unshift(result);
  if (scanHistory.value.length > 10) {
    scanHistory.value = scanHistory.value.slice(0, 10);
  }
  localStorage.setItem('qrScanHistory', JSON.stringify(scanHistory.value));
};

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

// Improved QR code detection with better parsing
const onDetect = async (detectedCodes: any[]) => {
  if (isLoading.value || !detectedCodes.length) return;

  const detectedCode = detectedCodes[0];
  const rawValue = detectedCode?.rawValue;

  try {
    isLoading.value = true;
    error.value = '';
    formErrors.value = {};

    let studentData: Student;
    let format: ScanResult['format'] = 'json';

    if (rawValue) {
      try {
        // Try to parse as JSON first
        studentData = JSON.parse(rawValue);
        console.log('QR Code parsed successfully (JSON):', studentData);
        format = 'json';
      } catch (parseErr) {
        console.log('QR is not JSON, trying alternative formats:', parseErr);
        
        // Try to extract data from other formats
        const parsed = parseQRData(rawValue);
        if (parsed) {
          studentData = parsed.student;
          format = parsed.format;
        } else {
          error.value = 'QR code format not recognized. Please use manual input.';
          return;
        }
      }
    } else {
      studentData = generateMockStudent();
      format = 'mock';
    }

    // Validate student data
    const validationError = validateStudentData(studentData);
    if (validationError) {
      error.value = validationError;
      return;
    }

    // Normalize year format
    if (studentData.year) {
      studentData.year = normalizeYearLevel(studentData.year);
    }

    // Add timestamp and ensure all fields
    studentData = {
      ...studentData,
      id: studentData.id || `scan-${Date.now()}`,
      timestamp: new Date().toISOString()
    };

    await new Promise(resolve => setTimeout(resolve, 800));
    
    const scanResult: ScanResult = {
      student: studentData,
      rawData: rawValue || 'mock',
      format
    };

    lastScannedStudent.value = studentData;
    saveToHistory(scanResult);
    emit('scanSuccess', studentData);
    error.value = '';

  } catch (err) {
    console.error('QR Scan error:', err);
    error.value = 'Scan processing error';
  } finally {
    isLoading.value = false;
  }
};

// Normalize year level to standard format
const normalizeYearLevel = (year: string): string => {
  const yearLower = year.toLowerCase().trim();
  
  const yearMappings: Record<string, string> = {
    '1': '1st', 'first': '1st', '1st year': '1st', '1st-year': '1st', 'year1': '1st', 'year 1': '1st',
    '2': '2nd', 'second': '2nd', '2nd year': '2nd', '2nd-year': '2nd', 'year2': '2nd', 'year 2': '2nd',
    '3': '3rd', 'third': '3rd', '3rd year': '3rd', '3rd-year': '3rd', 'year3': '3rd', 'year 3': '3rd',
    '4': '4th', 'fourth': '4th', '4th year': '4th', '4th-year': '4th', 'year4': '4th', 'year 4': '4th',
    '5': '5th', 'fifth': '5th', '5th year': '5th', '5th-year': '5th', 'year5': '5th', 'year 5': '5th'
  };

  return yearMappings[yearLower] || year;
};

// Validate student data structure
const validateStudentData = (data: any): string | null => {
  if (!data.student_id || typeof data.student_id !== 'string') {
    return 'Invalid or missing student ID';
  }
  if (!data.name || typeof data.name !== 'string') {
    return 'Invalid or missing student name';
  }
  if (data.year && typeof data.year !== 'string') {
    return 'Invalid year format';
  }
  if (data.course && typeof data.course !== 'string') {
    return 'Invalid course format';
  }
  if (data.section && typeof data.section !== 'string') {
    return 'Invalid section format';
  }
  
  // Validate year level if provided
  if (data.year && !/^(1st|2nd|3rd|4th|5th)$/.test(normalizeYearLevel(data.year))) {
    return 'Invalid year level. Must be 1st, 2nd, 3rd, 4th, or 5th year';
  }
  
  return null;
};

// Parse QR data from various formats
const parseQRData = (rawData: string): { student: Student; format: ScanResult['format'] } | null => {
  const trimmedData = rawData.trim();

  // Try CSV format: id,name,year,course,section
  const csvMatch = trimmedData.match(/^([^,]+),([^,]+),([^,]*),([^,]*),([^,]*)$/);
  if (csvMatch) {
    return {
      student: {
        id: `csv-${Date.now()}`,
        student_id: csvMatch[1].trim(),
        name: csvMatch[2].trim(),
        year: normalizeYearLevel(csvMatch[3].trim()) || '',
        course: csvMatch[4].trim() || '',
        section: csvMatch[5].trim() || '',
        timestamp: new Date().toISOString()
      },
      format: 'csv'
    };
  }
  
  // Try URL format with query parameters
  try {
    const url = new URL(trimmedData);
    const params = new URLSearchParams(url.search);
    
    if (params.get('student_id') || params.get('name')) {
      return {
        student: {
          id: `url-${Date.now()}`,
          student_id: params.get('student_id') || '',
          name: params.get('name') || '',
          year: normalizeYearLevel(params.get('year') || ''),
          course: params.get('course') || '',
          section: params.get('section') || '',
          timestamp: new Date().toISOString()
        },
        format: 'url'
      };
    }
  } catch (e) {
    // Not a URL, continue to next format
  }
  
  // Try key-value pairs separated by semicolons or pipes
  const keyValueRegex = /(\w+)[:=]\s*([^;|]+)/g;
  const keyValuePairs: Record<string, string> = {};
  let match;
  
  while ((match = keyValueRegex.exec(trimmedData)) !== null) {
    const [, key, value] = match;
    keyValuePairs[key.toLowerCase()] = value.trim();
  }
  
  if (keyValuePairs.student_id || keyValuePairs.name) {
    return {
      student: {
        id: `kv-${Date.now()}`,
        student_id: keyValuePairs.student_id || keyValuePairs.id || '',
        name: keyValuePairs.name || keyValuePairs.student_name || '',
        year: normalizeYearLevel(keyValuePairs.year || keyValuePairs.yr || keyValuePairs.year_level || ''),
        course: keyValuePairs.course || keyValuePairs.program || '',
        section: keyValuePairs.section || keyValuePairs.sec || '',
        timestamp: new Date().toISOString()
      },
      format: 'keyvalue'
    };
  }
  
  return null;
};

const generateMockStudent = (): Student => {
  const years = ['1st', '2nd', '3rd', '4th', '5th'];
  const courses = ['BSCS', 'BSIT', 'BSEE', 'BSME', 'BSBA'];
  
  return {
    id: 'dev-' + Date.now(),
    student_id: 'STU-' + Math.random().toString(36).substr(2, 9).toUpperCase(),
    name: 'Demo Student ' + Math.floor(Math.random() * 100),
    year: years[Math.floor(Math.random() * years.length)],
    course: courses[Math.floor(Math.random() * courses.length)],
    section: String.fromCharCode(65 + Math.floor(Math.random() * 3)),
    timestamp: new Date().toISOString()
  };
};

// Manual QR processing
const processManualInput = async () => {
  if (!manualQrInput.value.trim()) {
    error.value = 'Please enter QR code data';
    return;
  }

  try {
    isLoading.value = true;
    error.value = '';
    formErrors.value = {};

    let studentData: Student;
    let format: ScanResult['format'] = 'json';

    try {
      studentData = JSON.parse(manualQrInput.value);
      console.log('Manual QR Code parsed successfully:', studentData);
    } catch (parseErr) {
      error.value = 'Invalid JSON format. Please check your QR code data.';
      return;
    }

    // Validate the parsed data
    const validationError = validateStudentData(studentData);
    if (validationError) {
      error.value = validationError;
      return;
    }

    // Normalize year format
    if (studentData.year) {
      studentData.year = normalizeYearLevel(studentData.year);
    }

    studentData = {
      ...studentData,
      id: studentData.id || `manual-json-${Date.now()}`,
      timestamp: new Date().toISOString()
    };

    await new Promise(resolve => setTimeout(resolve, 800));
    
    const scanResult: ScanResult = {
      student: studentData,
      rawData: manualQrInput.value,
      format
    };

    lastScannedStudent.value = studentData;
    saveToHistory(scanResult);
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

// Process manual form submission
const processManualForm = async () => {
  if (!validateForm()) {
    error.value = 'Please fix the form errors before submitting';
    return;
  }

  try {
    isLoading.value = true;
    error.value = '';

    const studentData: Student = {
      id: 'manual-form-' + Date.now(),
      student_id: studentId.value.trim(),
      name: studentName.value.trim(),
      year: studentYear.value,
      course: studentCourse.value.trim(),
      section: studentSection.value.trim(),
      timestamp: new Date().toISOString()
    };

    await new Promise(resolve => setTimeout(resolve, 800));
    
    const scanResult: ScanResult = {
      student: studentData,
      rawData: 'manual_form',
      format: 'json'
    };

    lastScannedStudent.value = studentData;
    saveToHistory(scanResult);
    emit('scanSuccess', studentData);
    
    // Clear form
    studentId.value = '';
    studentName.value = '';
    studentYear.value = '';
    studentCourse.value = '';
    studentSection.value = '';
    formErrors.value = {};
    
    error.value = '';

  } catch (err) {
    console.error('Manual form error:', err);
    error.value = 'Processing error';
  } finally {
    isLoading.value = false;
  }
};

// Quick test scan
const quickTestScan = () => {
  const studentData = generateMockStudent();
  const scanResult: ScanResult = {
    student: studentData,
    rawData: 'quick_test',
    format: 'mock'
  };
  lastScannedStudent.value = studentData;
  saveToHistory(scanResult);
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
    error.value = 'Camera requires HTTPS. Please use manual mode or enable HTTPS.';
    return;
  }
  activeTab.value = 'camera';
  camera.value = 'auto';
  error.value = '';
  formErrors.value = {};
};

const switchToManual = () => {
  activeTab.value = 'manual';
  camera.value = 'off';
  error.value = '';
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
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

// Format timestamp
const formatTime = (timestamp: string) => {
  return new Date(timestamp).toLocaleTimeString();
};

// Get year level badge color
const getYearBadgeVariant = (year: string) => {
  const variants: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    '1st': 'default',
    '2nd': 'secondary',
    '3rd': 'outline',
    '4th': 'default',
    '5th': 'secondary'
  };
  return variants[year] || 'outline';
};
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 bg-background/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-background border rounded-xl shadow-xl w-full max-w-6xl h-[95vh] flex flex-col overflow-hidden">
      <!-- Header -->
      <div class="flex-shrink-0 border-b bg-gradient-to-r from-background to-muted/20">
        <div class="px-6 py-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                <QrCode class="h-6 w-6 text-primary-foreground" />
              </div>
              <div>
                <h2 class="text-2xl font-bold tracking-tight">Student QR Scanner</h2>
                <p class="text-sm text-muted-foreground">
                  Scan student ID cards to mark attendance
                </p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <Badge variant="secondary" class="text-xs">
                {{ scanHistory.length }} scans
              </Badge>
              <Button variant="outline" size="sm" @click="quickTestScan">
                <Zap class="h-4 w-4 mr-2" />
                Test Scan
              </Button>
              <Button variant="ghost" size="icon" @click="closeScanner">
                <X class="h-5 w-5" />
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="flex-1 overflow-hidden">
        <Tabs :value="activeTab" class="h-full flex flex-col">
          <div class="flex-shrink-0 border-b bg-muted/30">
            <div class="px-6">
              <TabsList class="w-full grid grid-cols-2 bg-transparent h-auto p-0">
                <TabsTrigger 
                  value="camera" 
                  @click="switchToCamera"
                  class="rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-transparent py-3"
                >
                  <Camera class="h-4 w-4 mr-2" />
                  Camera Scan
                  <Badge v-if="!isSecureContext" variant="destructive" class="ml-2 h-4 px-1 text-xs">
                    HTTPS
                  </Badge>
                </TabsTrigger>
                <TabsTrigger 
                  value="manual" 
                  @click="switchToManual"
                  class="rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-transparent py-3"
                >
                  <User class="h-4 w-4 mr-2" />
                  Manual Entry
                </TabsTrigger>
              </TabsList>
            </div>
          </div>

          <!-- Camera Tab Content -->
          <TabsContent value="camera" class="flex-1 m-0 p-6 overflow-auto data-[state=inactive]:hidden">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 h-full">
              <!-- Camera Container -->
              <div class="xl:col-span-2 flex flex-col">
                <div class="bg-black rounded-xl overflow-hidden relative flex-1 flex items-center justify-center min-h-[400px]">
                  <QrcodeStream
                    v-if="camera !== 'off' && isSecureContext"
                    :camera="camera"
                    :torch="torch"
                    @detect="onDetect"
                    @error="onError"
                    @camera-on="onCameraOn"
                    @camera-off="onCameraOff"
                    class="w-full h-full object-contain"
                  >
                    <!-- Scanning Frame Overlay -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                      <div class="relative">
                        <!-- Scanner Frame -->
                        <div class="w-64 h-64 relative">
                          <!-- Corner Brackets -->
                          <div class="absolute -top-2 -left-2 w-12 h-12 border-t-4 border-l-4 border-primary rounded-tl-lg"></div>
                          <div class="absolute -top-2 -right-2 w-12 h-12 border-t-4 border-r-4 border-primary rounded-tr-lg"></div>
                          <div class="absolute -bottom-2 -left-2 w-12 h-12 border-b-4 border-l-4 border-primary rounded-bl-lg"></div>
                          <div class="absolute -bottom-2 -right-2 w-12 h-12 border-b-4 border-r-4 border-primary rounded-br-lg"></div>
                          
                          <!-- Scanning Line -->
                          <div class="absolute top-0 left-0 right-0 h-1 bg-primary shadow-[0_0_20px_rgba(var(--primary),0.8)] animate-scan"></div>
                        </div>
                      </div>
                    </div>
                  </QrcodeStream>

                  <!-- Camera States -->
                  <div v-if="cameraStatus !== 'ready'" class="absolute inset-0 flex items-center justify-center bg-muted/50">
                    <Card class="max-w-sm w-full mx-4">
                      <CardContent class="p-6 text-center">
                        <div v-if="cameraStatus === 'loading'" class="space-y-4">
                          <RotateCw class="h-12 w-12 animate-spin mx-auto text-primary" />
                          <div>
                            <p class="font-semibold text-lg">Initializing Camera</p>
                            <p class="text-sm text-muted-foreground">Please wait...</p>
                          </div>
                        </div>
                        
                        <div v-else-if="cameraStatus === 'unsupported'" class="space-y-4">
                          <AlertCircle class="h-12 w-12 mx-auto text-yellow-500" />
                          <div>
                            <p class="font-semibold text-lg">Camera Not Available</p>
                            <p class="text-sm text-muted-foreground mt-2">
                              Camera requires HTTPS connection for security.
                            </p>
                          </div>
                          <Button @click="switchToManual" class="w-full">
                            <User class="h-4 w-4 mr-2" />
                            Switch to Manual Mode
                          </Button>
                        </div>
                        
                        <div v-else-if="cameraStatus === 'error'" class="space-y-4">
                          <AlertCircle class="h-12 w-12 mx-auto text-destructive" />
                          <div>
                            <p class="font-semibold text-lg">Camera Error</p>
                            <p class="text-sm text-muted-foreground mt-2">{{ error }}</p>
                          </div>
                          <div class="flex gap-2">
                            <Button @click="camera = 'auto'" variant="outline" class="flex-1">
                              <RotateCw class="h-4 w-4 mr-2" />
                              Retry
                            </Button>
                            <Button @click="switchToManual" class="flex-1">
                              Manual Mode
                            </Button>
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  </div>

                  <!-- Camera Controls -->
                  <div v-if="cameraStatus === 'ready'" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex gap-3">
                    <Button 
                      variant="secondary" 
                      size="lg" 
                      @click="switchCamera" 
                      :title="getCameraLabel()"
                      class="rounded-full shadow-xl backdrop-blur-sm bg-background/80"
                    >
                      <SwitchCamera class="h-5 w-5 mr-2" />
                      Switch
                    </Button>
                    <Button 
                      :variant="torch ? 'default' : 'secondary'"
                      size="lg" 
                      @click="toggleTorch" 
                      title="Toggle flashlight"
                      class="rounded-full shadow-xl backdrop-blur-sm bg-background/80"
                    >
                      <Flashlight class="h-5 w-5 mr-2" :fill="torch ? 'currentColor' : 'none'" />
                      Flash
                    </Button>
                  </div>
                </div>
                
                <!-- Instructions -->
                <div class="mt-4">
                  <Card class="bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200 dark:from-blue-950/20 dark:to-indigo-950/20">
                    <CardContent class="p-4">
                      <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-800 rounded-full flex items-center justify-center">
                          <QrCode class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                          <p class="text-sm font-medium text-blue-900 dark:text-blue-100">How to scan</p>
                          <p class="text-xs text-blue-700 dark:text-blue-300">Position the student's QR code within the frame. Ensure good lighting.</p>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </div>
              </div>
              
              <!-- Results Sidebar -->
              <div class="xl:col-span-1 space-y-6">
                <!-- Last Scanned Student -->
                <div v-if="lastScannedStudent" class="animate-fade-in">
                  <div class="flex items-center gap-2 mb-3">
                    <CheckCircle class="h-4 w-4 text-green-500" />
                    <p class="text-sm font-medium text-muted-foreground">Last Scanned</p>
                    <Badge variant="outline" class="ml-auto text-xs">
                      {{ formatTime(lastScannedStudent.timestamp!) }}
                    </Badge>
                  </div>
                  <Card class="border-green-200 bg-green-50/50 dark:bg-green-950/20 dark:border-green-800">
                    <CardContent class="p-4">
                      <div class="flex items-start gap-3">
                        <Avatar class="h-12 w-12 border-2 border-green-500 shrink-0">
                          <AvatarFallback class="bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 font-semibold">
                            {{ getInitials(lastScannedStudent.name) }}
                          </AvatarFallback>
                        </Avatar>
                        <div class="flex-1 min-w-0 space-y-2">
                          <div class="flex items-start gap-2">
                            <p class="font-semibold text-sm leading-tight truncate">{{ lastScannedStudent.name }}</p>
                            <Badge variant="default" class="bg-green-500 hover:bg-green-500 shrink-0">
                              Present
                            </Badge>
                          </div>
                          
                          <div class="space-y-1 text-xs">
                            <div class="flex items-center gap-2">
                              <Hash class="h-3 w-3 text-muted-foreground" />
                              <span class="text-muted-foreground">ID:</span>
                              <span class="font-mono">{{ lastScannedStudent.student_id }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                              <BookOpen class="h-3 w-3 text-muted-foreground" />
                              <span class="text-muted-foreground">Course:</span>
                              <span>{{ lastScannedStudent.course || 'N/A' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                              <GraduationCap class="h-3 w-3 text-muted-foreground" />
                              <span class="text-muted-foreground">Year:</span>
                              <Badge :variant="getYearBadgeVariant(lastScannedStudent.year)" class="text-xs">
                                {{ lastScannedStudent.year || 'N/A' }}
                              </Badge>
                            </div>
                            <div class="flex items-center gap-2">
                              <Calendar class="h-3 w-3 text-muted-foreground" />
                              <span class="text-muted-foreground">Section:</span>
                              <span>{{ lastScannedStudent.section || 'N/A' }}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </div>

                <!-- Scan History -->
                <div v-if="scanHistory.length > 0" class="flex-1">
                  <div class="flex items-center gap-2 mb-3">
                    <Calendar class="h-4 w-4 text-muted-foreground" />
                    <p class="text-sm font-medium text-muted-foreground">Recent Scans</p>
                    <Badge variant="outline" class="ml-auto text-xs">
                      {{ scanHistory.length }}
                    </Badge>
                  </div>
                  <div class="space-y-2 max-h-64 overflow-y-auto">
                    <Card v-for="(scan, index) in scanHistory.slice(0, 5)" :key="scan.student.id" 
                          class="group hover:border-primary/50 transition-colors">
                      <CardContent class="p-3">
                        <div class="flex items-center gap-2">
                          <Avatar class="h-8 w-8">
                            <AvatarFallback class="text-xs">
                              {{ getInitials(scan.student.name) }}
                            </AvatarFallback>
                          </Avatar>
                          <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ scan.student.name }}</p>
                            <div class="flex items-center gap-2 mt-1">
                              <Badge :variant="getYearBadgeVariant(scan.student.year)" class="text-xs" v-if="scan.student.year">
                                {{ scan.student.year }}
                              </Badge>
                              <span class="text-xs text-muted-foreground truncate">
                                {{ scan.student.student_id }}
                              </span>
                            </div>
                          </div>
                          <Badge variant="outline" class="text-xs shrink-0">
                            {{ formatTime(scan.student.timestamp!) }}
                          </Badge>
                        </div>
                      </CardContent>
                    </Card>
                  </div>
                </div>

                <!-- Empty State -->
                <div v-if="!lastScannedStudent && scanHistory.length === 0" 
                     class="text-center py-8 h-full flex flex-col items-center justify-center border-2 border-dashed rounded-xl border-muted">
                  <div class="mx-auto mb-3 w-12 h-12 bg-muted rounded-full flex items-center justify-center">
                    <QrCode class="h-6 w-6 text-muted-foreground" />
                  </div>
                  <p class="text-sm font-medium text-muted-foreground mb-1">No scans yet</p>
                  <p class="text-xs text-muted-foreground">
                    Scan a student QR code to get started
                  </p>
                </div>
              </div>
            </div>
          </TabsContent>

          <!-- Manual Input Tab Content -->
          <TabsContent value="manual" class="flex-1 m-0 p-6 overflow-auto data-[state=inactive]:hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 h-full">
              <!-- Manual Form -->
              <div class="space-y-6">
                <Card>
                  <CardHeader class="text-center pb-4">
                    <div class="mx-auto mb-4 w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center">
                      <User class="h-8 w-8 text-primary" />
                    </div>
                    <CardTitle>Manual Student Entry</CardTitle>
                    <p class="text-sm text-muted-foreground">
                      Enter student details to mark attendance
                    </p>
                  </CardHeader>
                  <CardContent class="space-y-4">
                    <div class="space-y-2">
                      <Label for="studentId" class="flex items-center gap-2">
                        <Hash class="h-4 w-4 text-muted-foreground" />
                        Student ID *
                      </Label>
                      <Input
                        id="studentId"
                        v-model="studentId"
                        placeholder="Enter student ID (e.g., STU-001)"
                        :disabled="isLoading"
                        @blur="validateField('student_id', studentId)"
                        :class="{ 'border-destructive': formErrors.student_id }"
                      />
                      <p v-if="formErrors.student_id" class="text-sm text-destructive">
                        {{ formErrors.student_id }}
                      </p>
                    </div>
                    
                    <div class="space-y-2">
                      <Label for="studentName" class="flex items-center gap-2">
                        <User class="h-4 w-4 text-muted-foreground" />
                        Full Name *
                      </Label>
                      <Input
                        id="studentName"
                        v-model="studentName"
                        placeholder="Enter student full name"
                        :disabled="isLoading"
                        @blur="validateField('name', studentName)"
                        :class="{ 'border-destructive': formErrors.name }"
                      />
                      <p v-if="formErrors.name" class="text-sm text-destructive">
                        {{ formErrors.name }}
                      </p>
                    </div>
                    
                    <div class="space-y-2">
                      <Label for="studentYear" class="flex items-center gap-2">
                        <GraduationCap class="h-4 w-4 text-muted-foreground" />
                        Year Level
                      </Label>
                      <Select v-model="studentYear" @update:modelValue="validateField('year', studentYear)">
                        <SelectTrigger :class="{ 'border-destructive': formErrors.year }">
                          <SelectValue placeholder="Select year level" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem v-for="level in yearLevels" :key="level.value" :value="level.value">
                            {{ level.label }}
                          </SelectItem>
                        </SelectContent>
                      </Select>
                      <p v-if="formErrors.year" class="text-sm text-destructive">
                        {{ formErrors.year }}
                      </p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                      <div class="space-y-2">
                        <Label for="studentCourse">Course</Label>
                        <Input
                          id="studentCourse"
                          v-model="studentCourse"
                          placeholder="e.g., BSCS"
                          :disabled="isLoading"
                          @blur="validateField('course', studentCourse)"
                          :class="{ 'border-destructive': formErrors.course }"
                        />
                        <p v-if="formErrors.course" class="text-sm text-destructive">
                          {{ formErrors.course }}
                        </p>
                      </div>
                      
                      <div class="space-y-2">
                        <Label for="studentSection">Section</Label>
                        <Input
                          id="studentSection"
                          v-model="studentSection"
                          placeholder="e.g., A"
                          :disabled="isLoading"
                          @blur="validateField('section', studentSection)"
                          :class="{ 'border-destructive': formErrors.section }"
                        />
                        <p v-if="formErrors.section" class="text-sm text-destructive">
                          {{ formErrors.section }}
                        </p>
                      </div>
                    </div>

                    <Button 
                      @click="processManualForm" 
                      :disabled="isLoading || !studentId.trim() || !studentName.trim() || Object.values(formErrors).some(e => e)" 
                      class="w-full"
                      size="lg"
                    >
                      <CheckCircle class="mr-2 h-5 w-5" />
                      {{ isLoading ? 'Processing...' : 'Mark Attendance' }}
                    </Button>
                  </CardContent>
                </Card>

                <!-- JSON Input (Alternative) -->
                <Card>
                  <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                      <QrCode class="h-5 w-5 text-muted-foreground" />
                      JSON Input
                    </CardTitle>
                    <p class="text-sm text-muted-foreground">
                      Paste JSON data from QR code
                    </p>
                  </CardHeader>
                  <CardContent class="space-y-4">
                    <div class="space-y-2">
                      <Label>QR Code JSON Data</Label>
                      <Textarea
                        v-model="manualQrInput"
                        placeholder='{"student_id": "STU-001", "name": "John Doe", "year": "2nd", "course": "BSCS", "section": "A"}'
                        class="min-h-[120px] font-mono text-sm resize-none"
                        :disabled="isLoading"
                      />
                      <p class="text-xs text-muted-foreground">
                        Enter valid JSON format with student information. Year should be 1st, 2nd, 3rd, 4th, or 5th.
                      </p>
                    </div>

                    <Button 
                      @click="processManualInput" 
                      :disabled="isLoading || !manualQrInput.trim()" 
                      class="w-full"
                      size="lg"
                    >
                      <CheckCircle class="mr-2 h-5 w-5" />
                      {{ isLoading ? 'Processing...' : 'Mark Attendance from JSON' }}
                    </Button>
                  </CardContent>
                </Card>
              </div>
              
              <!-- Results and History -->
              <div class="space-y-6">
                <!-- Last Scanned Student -->
                <div v-if="lastScannedStudent" class="animate-fade-in">
                  <div class="flex items-center gap-2 mb-3">
                    <CheckCircle class="h-4 w-4 text-green-500" />
                    <p class="text-sm font-medium text-muted-foreground">Last Added</p>
                    <Badge variant="outline" class="ml-auto text-xs">
                      {{ formatTime(lastScannedStudent.timestamp!) }}
                    </Badge>
                  </div>
                  <Card class="border-green-200 bg-green-50/50 dark:bg-green-950/20">
                    <CardContent class="p-4">
                      <div class="flex items-start gap-3">
                        <Avatar class="h-12 w-12 border-2 border-green-500 shrink-0">
                          <AvatarFallback class="bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 font-semibold">
                            {{ getInitials(lastScannedStudent.name) }}
                          </AvatarFallback>
                        </Avatar>
                        <div class="flex-1 min-w-0 space-y-2">
                          <div class="flex items-start gap-2">
                            <p class="font-semibold text-sm leading-tight truncate">{{ lastScannedStudent.name }}</p>
                            <Badge variant="default" class="bg-green-500 hover:bg-green-500 shrink-0">
                              Present
                            </Badge>
                          </div>
                          
                          <div class="space-y-1 text-xs">
                            <div class="flex items-center gap-2">
                              <Hash class="h-3 w-3 text-muted-foreground" />
                              <span class="text-muted-foreground">ID:</span>
                              <span class="font-mono">{{ lastScannedStudent.student_id }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                              <BookOpen class="h-3 w-3 text-muted-foreground" />
                              <span class="text-muted-foreground">Course:</span>
                              <span>{{ lastScannedStudent.course || 'N/A' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                              <GraduationCap class="h-3 w-3 text-muted-foreground" />
                              <span class="text-muted-foreground">Year:</span>
                              <Badge :variant="getYearBadgeVariant(lastScannedStudent.year)" class="text-xs">
                                {{ lastScannedStudent.year || 'N/A' }}
                              </Badge>
                            </div>
                            <div class="flex items-center gap-2">
                              <Calendar class="h-3 w-3 text-muted-foreground" />
                              <span class="text-muted-foreground">Section:</span>
                              <span>{{ lastScannedStudent.section || 'N/A' }}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </div>

                <!-- Scan History -->
                <Card v-if="scanHistory.length > 0">
                  <CardHeader class="pb-3">
                    <CardTitle class="text-sm flex items-center gap-2">
                      <Calendar class="h-4 w-4 text-muted-foreground" />
                      Scan History
                    </CardTitle>
                  </CardHeader>
                  <CardContent class="p-0">
                    <div class="max-h-80 overflow-y-auto">
                      <div v-for="(scan, index) in scanHistory" :key="scan.student.id" 
                           class="p-3 border-b last:border-b-0 hover:bg-muted/50 transition-colors">
                        <div class="flex items-center gap-3">
                          <Avatar class="h-8 w-8 shrink-0">
                            <AvatarFallback class="text-xs">
                              {{ getInitials(scan.student.name) }}
                            </AvatarFallback>
                          </Avatar>
                          <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ scan.student.name }}</p>
                            <div class="flex items-center gap-2 mt-1">
                              <Badge :variant="getYearBadgeVariant(scan.student.year)" class="text-xs" v-if="scan.student.year">
                                {{ scan.student.year }}
                              </Badge>
                              <span class="text-xs text-muted-foreground font-mono">{{ scan.student.student_id }}</span>
                            </div>
                          </div>
                          <div class="text-right shrink-0">
                            <Badge variant="outline" class="text-xs mb-1">
                              {{ formatTime(scan.student.timestamp!) }}
                            </Badge>
                            <p class="text-xs text-muted-foreground capitalize">{{ scan.format }}</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>

                <!-- Empty State -->
                <div v-if="!lastScannedStudent && scanHistory.length === 0" 
                     class="text-center py-12 h-full flex flex-col items-center justify-center border-2 border-dashed rounded-xl border-muted">
                  <div class="mx-auto mb-3 w-12 h-12 bg-muted rounded-full flex items-center justify-center">
                    <User class="h-6 w-6 text-muted-foreground" />
                  </div>
                  <p class="text-sm font-medium text-muted-foreground mb-1">No entries yet</p>
                  <p class="text-xs text-muted-foreground">
                    Add a student to get started
                  </p>
                </div>
              </div>
            </div>
          </TabsContent>
        </Tabs>
      </div>

      <!-- Loading Overlay -->
      <div v-if="isLoading" class="absolute inset-0 bg-background/80 backdrop-blur-sm flex items-center justify-center z-20">
        <Card class="max-w-sm">
          <CardContent class="p-6 text-center">
            <RotateCw class="h-10 w-10 animate-spin mx-auto text-primary mb-4" />
            <div>
              <p class="font-semibold text-lg mb-1">Processing</p>
              <p class="text-sm text-muted-foreground">Marking attendance...</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Error Alert -->
      <div v-if="error" class="flex-shrink-0 p-4 border-t bg-background animate-fade-in">
        <div class="max-w-4xl mx-auto">
          <Alert variant="destructive">
            <AlertCircle class="h-4 w-4" />
            <AlertDescription>{{ error }}</AlertDescription>
          </Alert>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
:deep(.qrcode-stream-camera) {
  width: 100% !important;
  height: 100% !important;
  object-fit: contain !important;
}

@keyframes scan {
  0% { top: 0; opacity: 0; }
  50% { opacity: 1; }
  100% { top: 100%; opacity: 0; }
}

@keyframes fade-in {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-scan {
  animation: scan 2s ease-in-out infinite;
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out;
}
</style>