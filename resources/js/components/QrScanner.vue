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
import { Camera, RotateCw, X, CheckCircle, AlertCircle, QrCode, Upload, Zap, SwitchCamera, Flashlight, User, BookOpen, Calendar, Hash, GraduationCap, FileUp, Play, Square } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

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
  needsLookup?: boolean; // Flag to indicate database lookup needed
}

interface ScanResult {
  student: Student;
  rawData: string;
  format: 'json' | 'csv' | 'url' | 'keyvalue' | 'mock' | 'file' | 'name-course';
}

// Props
const props = defineProps<{
  show: boolean;
  sessionId?: number | null;
}>();

// Emits
const emit = defineEmits<{
  close: [];
  scanSuccess: [student: Student];
  batchScanSuccess: [students: Student[]];
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
const activeTab = ref<'camera' | 'manual' | 'upload'>('camera');
const scanHistory = ref<ScanResult[]>([]);
const isScanning = ref(false);
const pendingScans = ref<Student[]>([]);

// File upload state
const fileInput = ref<HTMLInputElement | null>(null);
const uploadedFile = ref<File | null>(null);
const uploadProgress = ref<number>(0);
const uploadedStudents = ref<Student[]>([]);
const uploadSuccess = ref<string>('');
const uploadError = ref<string>('');
const isUploading = ref<boolean>(false);

// Manual form fields with validation - simplified for name+course only
const studentName = ref<string>('');
const studentCourse = ref<string>('');
const formErrors = ref<Record<string, string>>({});

// Optional fields (for display purposes only)
const studentId = ref<string>('');
const studentYear = ref<string>(''); // 1st, 2nd, 3rd, 4th, 5th
const studentSection = ref<string>('');

// Year level options
const yearLevels = [
  { value: '1st', label: '1st Year' },
  { value: '2nd', label: '2nd Year' },
  { value: '3rd', label: '3rd Year' },
  { value: '4th', label: '4th Year' },
  { value: '5th', label: '5th Year' }
];

// Form validation rules - simplified for name+course
const validationRules = {
  name: (value: string) => {
    if (!value.trim()) return 'Name is required';
    if (value.length < 2) return 'Name must be at least 2 characters';
    if (!/^[a-zA-Z\s.'-]+$/.test(value)) return 'Name can only contain letters, spaces, and basic punctuation';
    return '';
  },
  course: (value: string) => {
    if (!value.trim()) return 'Course is required';
    if (value.length < 2) return 'Course must be at least 2 characters';
    if (!/^(BS|Bachelor|BSCS|BSIT|BSEE|BSME|BSBA)/i.test(value)) return 'Please enter a valid course (e.g., BSCS, BSIT)';
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

// Validate entire form - simplified for name+course
const validateForm = (): boolean => {
  const fields = {
    name: studentName.value,
    course: studentCourse.value
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
    isScanning.value = true;
  } else {
    camera.value = 'off';
    isScanning.value = false;
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

    // Handle database lookup if needed
    if (studentData.needsLookup) {
      try {
        const lookupResult = await lookupStudentByName(studentData.name, studentData.course);
        if (lookupResult) {
          studentData = {
            ...studentData,
            student_id: lookupResult.student_id,
            year: lookupResult.year,
            course: lookupResult.course,
            section: lookupResult.section,
            needsLookup: false
          };
          console.log('Student found in database:', studentData);
        } else {
          error.value = `Student "${studentData.name}" with course "${studentData.course}" not found in this class or database. Please verify the student is enrolled.`;
          return;
        }
      } catch (lookupErr) {
        console.warn('Failed to lookup student:', lookupErr);
        error.value = 'Database lookup failed. Please try again.';
        return;
      }
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

    // Add to pending scans instead of immediately emitting
    const existingIndex = pendingScans.value.findIndex(s => s.student_id === studentData.student_id);
    if (existingIndex === -1) {
      pendingScans.value.push(studentData);
    }

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
  // Allow empty student_id if this student needs database lookup
  if (!data.needsLookup && (!data.student_id || typeof data.student_id !== 'string')) {
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
  
  // Validate year level if provided (skip validation if empty and needs lookup)
  if (data.year && data.year.trim() !== '' && !/^(1st|2nd|3rd|4th|5th)$/.test(normalizeYearLevel(data.year))) {
    return 'Invalid year level. Must be 1st, 2nd, 3rd, 4th, or 5th year';
  }
  
  return null;
};

// Get current CSRF token
const getCsrfToken = (): string => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (!token) {
    console.warn('CSRF token not found in meta tag');
  }
  return token || '';
};

// Lookup student by name in database - use existing QR scan endpoint
const lookupStudentByName = async (name: string, course: string): Promise<Student | null> => {
  try {
    // Create QR data format that the backend already handles
    const qrData = `${name},${course}`;
    
    // Check if sessionId is provided for direct marking  
    if (props.sessionId) {
      console.log('Making QR scan request:', { sessionId: props.sessionId, qrData });
      
      const response = await fetch(`/teacher/attendance/${props.sessionId}/qr-scan`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ qr_data: qrData })
      });

      console.log('QR scan response status:', response.status);

      if (response.status === 419) {
        throw new Error('CSRF token mismatch. Please refresh the page and try again.');
      }

      if (response.ok) {
        const data = await response.json();
        console.log('QR scan response data:', data);
        
        if (data.success) {
          // Student was found and marked as present automatically
          console.log('Student marked as present:', data.student);
          return {
            id: data.student.id?.toString() || `db-${Date.now()}`,
            student_id: data.student.student_id,
            name: data.student.name,
            year: data.student.year || '',
            course: data.student.course || course,
            section: data.student.section || ''
          };
        } else {
          throw new Error(data.message || 'Student not found');
        }
      } else {
        let errorMessage = `Request failed with status ${response.status}`;
        try {
          const errorData = await response.json();
          errorMessage = errorData.message || errorMessage;
        } catch (e) {
          // Failed to parse error JSON, use status message
        }
        throw new Error(errorMessage);
      }
    } else {
      // If no session ID, just return null (can't lookup without session context)
      throw new Error('No attendance session available');
    }
  } catch (err) {
    console.error('Student lookup failed:', err);
    throw err;
  }
};

// Parse QR data from various formats
const parseQRData = (rawData: string): { student: Student; format: ScanResult['format'] } | null => {
  const trimmedData = rawData.trim();
  console.log('🔍 Raw QR Data:', JSON.stringify(rawData));
  console.log('🔍 Trimmed QR Data:', JSON.stringify(trimmedData));

  // Priority 0: Super simple fallback - any comma-separated data with at least 2 parts
  console.log('🔍 Checking simple fallback - has comma:', trimmedData.includes(','));
  
  if (trimmedData.includes(',')) {
    const simpleParts = trimmedData.split(',');
    console.log('🔍 Simple split result:', simpleParts);
    console.log('🔍 Simple parts length:', simpleParts.length);
    
    if (simpleParts.length >= 2) {
      const name = simpleParts[0].trim();
      const course = simpleParts[1].trim();
      
      console.log('🔍 Simple parser - Name after trim:', JSON.stringify(name));
      console.log('🔍 Simple parser - Course after trim:', JSON.stringify(course));
      console.log('🔍 Simple parser - Name check:', !!name);
      console.log('🔍 Simple parser - Course check:', !!course);
      
      if (name && course) {
        console.log('✅ Simple fallback parser SUCCESS - Name:', name, 'Course:', course);
        return {
          student: {
            id: `simple-${Date.now()}`,
            student_id: '',
            name: name,
            year: '',
            course: course,
            section: '',
            timestamp: new Date().toISOString(),
            needsLookup: true
          },
          format: 'name-course'
        };
      } else {
        console.log('❌ Simple fallback failed - empty name or course');
      }
    } else {
      console.log('❌ Simple fallback failed - not enough parts');
    }
  } else {
    console.log('❌ Simple fallback failed - no comma found');
  }

  // Priority 1: Handle your specific format "EJ FAYE A. DULAY,BSCS,," - with trailing commas
  const nameCourseParts = trimmedData.split(',').map(part => part.trim()).filter(part => part.length > 0);
  console.log('🔍 Split parts:', nameCourseParts);
  
  if (nameCourseParts.length >= 2) {
    const studentName = nameCourseParts[0];
    const course = nameCourseParts[1];
    
    console.log('🔍 Student Name:', JSON.stringify(studentName));
    console.log('🔍 Course:', JSON.stringify(course));
    
    // Check if we have a valid name and course
    if (studentName.length > 0 && course.length > 0) {
      // Simplified course validation - just check if it's not empty and looks like a course
      console.log('🔍 Course validation - checking:', course);
      
      // More flexible course pattern - accept any BS followed by letters, or common course codes
      const coursePattern = /^(BS|BSCS|BSIT|BSEE|BSME|BSBA|Bachelor|CS|IT|EE|ME|BA)/i;
      const courseValid = coursePattern.test(course);
      
      console.log('🔍 Course pattern test result:', courseValid);
      
      if (courseValid) {
        console.log('✅ QR Code detected - Name:', studentName, 'Course:', course);
        
        return {
          student: {
            id: `name-course-${Date.now()}`,
            student_id: '', // Will be looked up from database
            name: studentName,
            year: '', // Will be looked up from database
            course: course, // Keep original course format
            section: '', // Will be looked up from database
            timestamp: new Date().toISOString(),
            needsLookup: true // This will trigger database lookup to get full student info
          },
          format: 'name-course'
        };
      } else {
        console.log('❌ Course validation failed for:', course);
      }
    } else {
      console.log('❌ Name or course is empty - Name:', studentName, 'Course:', course);
    }
  } else {
    console.log('❌ Not enough parts after split. Need at least 2, got:', nameCourseParts.length);
  }

  // Priority 2: Try strict regex pattern as fallback
  const nameOnlyMatch = trimmedData.match(/^([^,]+),\s*(BSCS|BSIT|BSEE|BSME|BSBA|BS[A-Z]{2,4}|Bachelor[^,]*),*\s*$/i);
  if (nameOnlyMatch) {
    const studentName = nameOnlyMatch[1].trim();
    const course = nameOnlyMatch[2].trim();
    
    console.log('QR Code detected (regex) - Name:', studentName, 'Course:', course);
    
    return {
      student: {
        id: `regex-${Date.now()}`,
        student_id: '', // Will be looked up from database
        name: studentName,
        year: '', // Will be looked up from database
        course: course, // Keep original course format
        section: '', // Will be looked up from database
        timestamp: new Date().toISOString(),
        needsLookup: true // This will trigger database lookup to get full student info
      },
      format: 'name-course'
    };
  }

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
  
  console.log('❌ No parsing method succeeded for QR data:', JSON.stringify(trimmedData));
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

// Manual QR processing - simplified for name,course format
const processManualInput = async () => {
  if (!manualQrInput.value.trim()) {
    error.value = 'Please enter QR code data';
    return;
  }

  try {
    isLoading.value = true;
    error.value = '';
    formErrors.value = {};

    const rawData = manualQrInput.value.trim();
    let studentData: Student;
    let format: ScanResult['format'] = 'name-course';

    // Try to parse as simple name,course format first (handle trailing commas)
    const parts = rawData.split(',').map(part => part.trim()).filter(part => part.length > 0);
    if (parts.length >= 2) {
      const name = parts[0];
      const course = parts[1];
      
      console.log('Manual QR processing - Name:', name, 'Course:', course);
      
      // Look up student in database
      const lookupResult = await lookupStudentByName(name, course);
      
      if (lookupResult) {
        studentData = {
          ...lookupResult,
          timestamp: new Date().toISOString()
        };
      } else {
        error.value = `Student "${name}" with course "${course}" not found`;
        return;
      }
    } else {
      // Try JSON format as fallback
      try {
        const jsonData = JSON.parse(rawData);
        if (jsonData.name && jsonData.course) {
          const lookupResult = await lookupStudentByName(jsonData.name, jsonData.course);
          if (lookupResult) {
            studentData = {
              ...lookupResult,
              timestamp: new Date().toISOString()
            };
            format = 'json';
          } else {
            error.value = `Student "${jsonData.name}" not found`;
            return;
          }
        } else {
          error.value = 'Invalid format. Use "Name,Course" or valid JSON with name and course';
          return;
        }
      } catch (parseErr) {
        error.value = 'Invalid format. Please use "Name,Course" format (e.g., "Juan Dela Cruz,BSCS")';
        return;
      }
    }

    const scanResult: ScanResult = {
      student: studentData,
      rawData: rawData,
      format
    };

    lastScannedStudent.value = studentData;
    saveToHistory(scanResult);
    emit('scanSuccess', studentData);
    manualQrInput.value = '';
    error.value = '';

  } catch (err) {
    console.error('Manual scan error:', err);
    error.value = err instanceof Error ? err.message : 'Student lookup failed';
  } finally {
    isLoading.value = false;
  }
};

// Process manual form submission - simplified for name+course lookup
const processManualForm = async () => {
  if (!validateForm()) {
    error.value = 'Please fix the form errors before submitting';
    return;
  }

  try {
    isLoading.value = true;
    error.value = '';
    formErrors.value = {};

    const name = studentName.value.trim();
    const course = studentCourse.value.trim();

    // Look up student in database using name and course
    const lookupResult = await lookupStudentByName(name, course);
    
    if (lookupResult) {
      const studentData: Student = {
        ...lookupResult,
        timestamp: new Date().toISOString()
      };

      const scanResult: ScanResult = {
        student: studentData,
        rawData: `${name},${course}`,
        format: 'name-course'
      };

      lastScannedStudent.value = studentData;
      saveToHistory(scanResult);
      emit('scanSuccess', studentData);
      
      // Clear form
      studentName.value = '';
      studentCourse.value = '';
      formErrors.value = {};
      
      error.value = '';
    } else {
      error.value = `Student "${name}" with course "${course}" not found in database`;
    }

  } catch (err) {
    console.error('Manual form error:', err);
    error.value = err instanceof Error ? err.message : 'Student lookup failed';
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
  isScanning.value = false;
  // Clear pending scans when closing without stopping
  pendingScans.value = [];
  emit('close');
};

// Toggle scanning
const toggleScanning = () => {
  if (isScanning.value) {
    // Stop scanning and process all pending scans
    camera.value = 'off';
    isScanning.value = false;

    // Emit all pending scans as batch
    if (pendingScans.value.length > 0) {
      emit('batchScanSuccess', [...pendingScans.value]);
      pendingScans.value = []; // Clear pending scans
    }
  } else {
    // Start scanning
    if (activeTab.value === 'camera' && isSecureContext.value) {
      camera.value = 'auto';
      error.value = '';
      cameraStatus.value = 'loading';
      isScanning.value = true;
    }
  }
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

// Switch to upload tab
const switchToUpload = () => {
  activeTab.value = 'upload';
  camera.value = 'off';
  error.value = '';
  uploadError.value = '';
};

// Handle file upload
const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  
  if (file) {
    uploadedFile.value = file;
    uploadError.value = '';
    uploadSuccess.value = '';
    processUploadedFile(file);
  }
};

// Process uploaded CSV file
const processUploadedFile = async (file: File) => {
  if (!file) return;

  // Check file type
  const allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'text/plain'];
  if (!allowedTypes.includes(file.type) && !file.name.endsWith('.csv')) {
    uploadError.value = 'Please upload a CSV file';
    return;
  }

  try {
    isUploading.value = true;
    uploadError.value = '';
    uploadedStudents.value = [];
    uploadProgress.value = 0;

    const text = await file.text();
    const lines = text.split('\n').filter(line => line.trim());

    if (lines.length === 0) {
      uploadError.value = 'File is empty';
      isUploading.value = false;
      return;
    }

    // Parse CSV
    const students: Student[] = [];
    let hasHeader = false;

    // Check if first line is header
    const firstLine = lines[0].toLowerCase();
    if (firstLine.includes('student_id') || firstLine.includes('name') || firstLine.includes('id')) {
      hasHeader = true;
      lines.shift(); // Remove header
    }

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i].trim();
      if (!line) continue;

      // Split by comma, handling quoted values
      const values = line.split(',').map(v => v.trim().replace(/^["']|["']$/g, ''));

      if (values.length < 2) {
        console.warn(`Skipping invalid line ${i + 1}: ${line}`);
        continue;
      }

      // Parse student data from CSV
      // Expected format: student_id, name, year, course, section
      const student: Student = {
        id: `upload-${Date.now()}-${i}`,
        student_id: values[0] || '',
        name: values[1] || '',
        year: values[2] ? normalizeYearLevel(values[2]) : '',
        course: values[3] || '',
        section: values[4] || '',
        timestamp: new Date().toISOString()
      };

      // Validate student data
      if (student.student_id && student.name) {
        students.push(student);
      }

      uploadProgress.value = Math.round(((i + 1) / lines.length) * 100);
    }

    if (students.length === 0) {
      uploadError.value = 'No valid student data found in file';
      isUploading.value = false;
      return;
    }

    uploadedStudents.value = students;
    uploadSuccess.value = `Successfully parsed ${students.length} student(s) from file`;
    uploadProgress.value = 100;

  } catch (err) {
    console.error('File upload error:', err);
    uploadError.value = `Failed to process file: ${err}`;
  } finally {
    isUploading.value = false;
  }
};

// Save all uploaded students
const saveUploadedStudents = async () => {
  if (uploadedStudents.value.length === 0) {
    uploadError.value = 'No students to save';
    return;
  }

  try {
    isLoading.value = true;
    uploadError.value = '';

    let savedCount = 0;
    let errorCount = 0;

    // Process each student
    for (const student of uploadedStudents.value) {
      const scanResult: ScanResult = {
        student,
        rawData: 'bulk_upload',
        format: 'file'
      };
      
      saveToHistory(scanResult);
      emit('scanSuccess', student);
      savedCount++;
      
      // Add small delay between emits
      await new Promise(resolve => setTimeout(resolve, 100));
    }

    uploadSuccess.value = `Successfully added ${savedCount} student(s)`;
    
    // Clear upload state after short delay
    setTimeout(() => {
      uploadedStudents.value = [];
      uploadedFile.value = null;
      uploadProgress.value = 0;
      if (fileInput.value) {
        fileInput.value.value = '';
      }
    }, 2000);

  } catch (err) {
    console.error('Save students error:', err);
    uploadError.value = 'Failed to save students. Please try again.';
  } finally {
    isLoading.value = false;
  }
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
                <h2 class="text-2xl font-bold tracking-tight">Student Attendance Scanner</h2>
                <p class="text-sm text-muted-foreground">
                  Scan name+course QR codes to automatically mark students as present
                </p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <Badge variant="secondary" class="text-xs">
                {{ pendingScans.length }} pending • {{ scanHistory.length }} total
              </Badge>
              <Button variant="outline" size="sm" @click="quickTestScan">
                <Zap class="h-4 w-4 mr-2" />
                Test Scan
              </Button>
              <Button
                v-if="activeTab === 'camera'"
                :variant="isScanning ? 'destructive' : 'default'"
                size="sm"
                @click="toggleScanning"
                :disabled="cameraStatus === 'error' || cameraStatus === 'unsupported'"
              >
                <Play v-if="!isScanning" class="h-4 w-4 mr-2" />
                <Square v-if="isScanning" class="h-4 w-4 mr-2" />
                {{ isScanning ? 'Stop Scanning' : 'Start Scanning' }}
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
              <TabsList class="w-full grid grid-cols-3 bg-transparent h-auto p-0">
                <TabsTrigger 
                  value="camera" 
                  @click="switchToCamera"
                  class="rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-transparent py-3"
                >
                  <Camera class="h-4 w-4 mr-2" />
                  QR Scan (Auto Present)
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
                  Manual Lookup
                </TabsTrigger>
                <TabsTrigger 
                  value="upload" 
                  @click="switchToUpload"
                  class="rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-transparent py-3"
                >
                  <FileUp class="h-4 w-4 mr-2" />
                  File Upload
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
                  <div v-if="cameraStatus !== 'ready' && isScanning" class="absolute inset-0 flex items-center justify-center bg-muted/50">
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

                  <!-- Stopped State -->
                  <div v-if="!isScanning && cameraStatus !== 'ready'" class="absolute inset-0 flex items-center justify-center bg-muted/50">
                    <Card class="max-w-sm w-full mx-4">
                      <CardContent class="p-6 text-center">
                        <div class="space-y-4">
                          <Camera class="h-12 w-12 mx-auto text-muted-foreground" />
                          <div>
                            <p class="font-semibold text-lg">Camera Stopped</p>
                            <p class="text-sm text-muted-foreground">Click "Start Scanning" to begin scanning QR codes</p>
                          </div>
                          <Button @click="toggleScanning" class="w-full">
                            <Play class="h-4 w-4 mr-2" />
                            Start Scanning
                          </Button>
                        </div>
                      </CardContent>
                    </Card>
                  </div>

                  <!-- Camera Controls -->
                  <div v-if="cameraStatus === 'ready' && isScanning" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex gap-3">
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
                          <p class="text-xs text-blue-700 dark:text-blue-300">Scan QR codes containing student name and course (e.g., "EJ FAYE A. DULAY,BSCS,,"). Students will be added to pending list. Click "Stop Scanning" to mark all scanned students as present.</p>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </div>
              </div>
              
              <!-- Results Sidebar -->
              <div class="xl:col-span-1 space-y-6">
                <!-- Pending Scans Preview -->
                <div v-if="pendingScans.length > 0" class="animate-fade-in">
                  <div class="flex items-center gap-2 mb-3">
                    <Clock class="h-4 w-4 text-orange-500" />
                    <p class="text-sm font-medium text-muted-foreground">Pending Scans ({{ pendingScans.length }})</p>
                    <Badge variant="outline" class="ml-auto text-xs bg-orange-50 text-orange-700 border-orange-200">
                      Will be marked present when stopped
                    </Badge>
                  </div>
                  <div class="space-y-2 max-h-64 overflow-y-auto">
                    <Card v-for="(student, index) in pendingScans.slice(0, 5)" :key="student.id"
                          class="group hover:border-orange-300 transition-colors border-orange-200 bg-orange-50/50 dark:bg-orange-950/20">
                      <CardContent class="p-3">
                        <div class="flex items-center gap-2">
                          <Avatar class="h-8 w-8 shrink-0">
                            <AvatarFallback class="text-xs bg-orange-100 text-orange-700">
                              {{ getInitials(student.name) }}
                            </AvatarFallback>
                          </Avatar>
                          <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ student.name }}</p>
                            <div class="flex items-center gap-2 mt-1">
                              <Badge variant="outline" class="text-xs font-mono">
                                {{ student.student_id }}
                              </Badge>
                              <Badge :variant="getYearBadgeVariant(student.year)" class="text-xs" v-if="student.year">
                                {{ student.year }}
                              </Badge>
                            </div>
                          </div>
                          <Badge variant="outline" class="text-xs shrink-0">
                            {{ formatTime(student.timestamp!) }}
                          </Badge>
                        </div>
                      </CardContent>
                    </Card>
                    <div v-if="pendingScans.length > 5" class="text-center py-2">
                      <p class="text-xs text-muted-foreground">+{{ pendingScans.length - 5 }} more students</p>
                    </div>
                  </div>
                </div>

                <!-- Last Scanned Student -->
                <div v-if="lastScannedStudent && pendingScans.length === 0" class="animate-fade-in">
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
                    <CardTitle>Manual Student Lookup</CardTitle>
                    <p class="text-sm text-muted-foreground">
                      Enter student name and course to find and mark as present
                    </p>
                  </CardHeader>
                  <CardContent class="space-y-4">
                    <div class="space-y-2">
                      <Label for="studentName" class="flex items-center gap-2">
                        <User class="h-4 w-4 text-muted-foreground" />
                        Full Name *
                      </Label>
                      <Input
                        id="studentName"
                        v-model="studentName"
                        placeholder="Enter student full name (e.g., Juan Dela Cruz)"
                        :disabled="isLoading"
                        @blur="validateField('name', studentName)"
                        :class="{ 'border-destructive': formErrors.name }"
                      />
                      <p v-if="formErrors.name" class="text-sm text-destructive">
                        {{ formErrors.name }}
                      </p>
                    </div>
                    
                    <div class="space-y-2">
                      <Label for="studentCourse" class="flex items-center gap-2">
                        <BookOpen class="h-4 w-4 text-muted-foreground" />
                        Course *
                      </Label>
                      <Input
                        id="studentCourse"
                        v-model="studentCourse"
                        placeholder="Enter course (e.g., BSCS, BSIT, BSEE)"
                        :disabled="isLoading"
                        @blur="validateField('course', studentCourse)"
                        :class="{ 'border-destructive': formErrors.course }"
                      />
                      <p v-if="formErrors.course" class="text-sm text-destructive">
                        {{ formErrors.course }}
                      </p>
                    </div>

                    <Alert class="border-blue-200 bg-blue-50 text-blue-900 dark:bg-blue-950 dark:text-blue-100">
                      <CheckCircle class="h-4 w-4 text-blue-600" />
                      <AlertDescription class="text-sm">
                        Only name and course are required. The system will automatically look up the student and mark them as present.
                      </AlertDescription>
                    </Alert>

                    <Button 
                      @click="processManualForm" 
                      :disabled="isLoading || !studentName.trim() || !studentCourse.trim() || Object.values(formErrors).some(e => e)" 
                      class="w-full"
                      size="lg"
                    >
                      <CheckCircle class="mr-2 h-5 w-5" />
                      {{ isLoading ? 'Finding Student & Marking Present...' : 'Find Student & Mark Present' }}
                    </Button>
                  </CardContent>
                </Card>

                <!-- Simple Text Input (Alternative) -->
                <Card>
                  <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                      <QrCode class="h-5 w-5 text-muted-foreground" />
                      QR Code Text
                    </CardTitle>
                    <p class="text-sm text-muted-foreground">
                      Paste raw QR code data (name,course format)
                    </p>
                  </CardHeader>
                  <CardContent class="space-y-4">
                    <div class="space-y-2">
                      <Label>QR Code Data</Label>
                      <Textarea
                        v-model="manualQrInput"
                        placeholder='EJ FAYE A. DULAY,BSCS,,'
                        class="min-h-[120px] font-mono text-sm resize-none"
                        :disabled="isLoading"
                      />
                      <p class="text-xs text-muted-foreground">
                        Enter QR code data in the format: "Student Name,Course,," (e.g., "EJ FAYE A. DULAY,BSCS,,")
                      </p>
                    </div>

                    <Button 
                      @click="processManualInput" 
                      :disabled="isLoading || !manualQrInput.trim()" 
                      class="w-full"
                      size="lg"
                    >
                      <CheckCircle class="mr-2 h-5 w-5" />
                      {{ isLoading ? 'Finding Student...' : 'Find Student & Mark Present' }}
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

          <!-- File Upload Tab Content -->
          <TabsContent value="upload" class="flex-1 m-0 p-6 overflow-auto data-[state=inactive]:hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 h-full">
              <!-- Upload Section -->
              <div class="space-y-6">
                <Card>
                  <CardHeader class="text-center pb-4">
                    <div class="mx-auto mb-4 w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center">
                      <FileUp class="h-8 w-8 text-primary" />
                    </div>
                    <CardTitle>Bulk Student Upload</CardTitle>
                    <p class="text-sm text-muted-foreground">
                      Upload a CSV file to import multiple students at once
                    </p>
                  </CardHeader>

                  <CardContent class="space-y-4">
                    <!-- File Input -->
                    <div class="space-y-2">
                      <Label>Select CSV File</Label>
                      <div class="flex gap-2">
                        <Input
                          ref="fileInput"
                          type="file"
                          accept=".csv,text/csv,application/vnd.ms-excel"
                          @change="handleFileSelect"
                          :disabled="isLoading || isUploading"
                          class="flex-1"
                        />
                      </div>
                      <p class="text-xs text-muted-foreground">
                        CSV format: student_id, name, year, course, section
                      </p>
                    </div>

                    <!-- Upload Progress -->
                    <div v-if="isUploading" class="space-y-2">
                      <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">Processing file...</span>
                        <span class="font-medium">{{ uploadProgress }}%</span>
                      </div>
                      <div class="w-full bg-muted rounded-full h-2 overflow-hidden">
                        <div 
                          class="bg-primary h-full transition-all duration-300 ease-out" 
                          :style="{ width: `${uploadProgress}%` }"
                        ></div>
                      </div>
                    </div>

                    <!-- Success Message -->
                    <Alert v-if="uploadSuccess" class="border-green-200 bg-green-50 text-green-900 dark:bg-green-950 dark:text-green-100">
                      <CheckCircle class="h-4 w-4 text-green-600" />
                      <AlertDescription>{{ uploadSuccess }}</AlertDescription>
                    </Alert>

                    <!-- Error Message -->
                    <Alert v-if="uploadError" variant="destructive">
                      <AlertCircle class="h-4 w-4" />
                      <AlertDescription>{{ uploadError }}</AlertDescription>
                    </Alert>

                    <!-- CSV Format Guide -->
                    <Card class="bg-muted/50">
                      <CardHeader class="pb-3">
                        <CardTitle class="text-sm">CSV Format Example</CardTitle>
                      </CardHeader>
                      <CardContent class="space-y-2">
                        <div class="bg-background rounded-md p-3 font-mono text-xs overflow-x-auto">
                          <div class="text-muted-foreground mb-2">// With header (recommended):</div>
                          <div>student_id,name,year,course,section</div>
                          <div>STU-001,John Doe,1st,BSCS,A</div>
                          <div>STU-002,Jane Smith,2nd,BSIT,B</div>
                          <div class="mt-3 text-muted-foreground">// Without header:</div>
                          <div>STU-001,John Doe,1st,BSCS,A</div>
                          <div>STU-002,Jane Smith,2nd,BSIT,B</div>
                        </div>
                        <p class="text-xs text-muted-foreground">
                          • First two columns (student_id, name) are required<br>
                          • Year must be: 1st, 2nd, 3rd, 4th, or 5th<br>
                          • Course and section are optional
                        </p>
                      </CardContent>
                    </Card>

                    <!-- Save Button -->
                    <Button 
                      @click="saveUploadedStudents" 
                      :disabled="uploadedStudents.length === 0 || isLoading"
                      class="w-full"
                      size="lg"
                    >
                      <CheckCircle class="mr-2 h-5 w-5" />
                      {{ isLoading ? 'Saving...' : `Save ${uploadedStudents.length} Student(s)` }}
                    </Button>
                  </CardContent>
                </Card>
              </div>

              <!-- Preview Section -->
              <div class="space-y-6">
                <Card v-if="uploadedStudents.length > 0">
                  <CardHeader>
                    <CardTitle class="text-sm flex items-center justify-between">
                      <span>Parsed Students</span>
                      <Badge variant="secondary">{{ uploadedStudents.length }}</Badge>
                    </CardTitle>
                  </CardHeader>
                  <CardContent class="p-0">
                    <div class="max-h-[600px] overflow-y-auto">
                      <div v-for="(student, index) in uploadedStudents" :key="student.id" 
                           class="p-3 border-b last:border-b-0 hover:bg-muted/50 transition-colors">
                        <div class="flex items-start gap-3">
                          <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                            <span class="text-xs font-semibold text-primary">{{ index + 1 }}</span>
                          </div>
                          <div class="flex-1 min-w-0 space-y-1">
                            <p class="text-sm font-medium truncate">{{ student.name }}</p>
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                              <Badge variant="outline" class="font-mono">
                                {{ student.student_id }}
                              </Badge>
                              <Badge v-if="student.year" :variant="getYearBadgeVariant(student.year)">
                                {{ student.year }}
                              </Badge>
                              <span v-if="student.course" class="text-muted-foreground">
                                {{ student.course }}
                              </span>
                              <span v-if="student.section" class="text-muted-foreground">
                                Sec {{ student.section }}
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>

                <!-- Empty State -->
                <div v-if="uploadedStudents.length === 0" 
                     class="text-center py-12 h-full flex flex-col items-center justify-center border-2 border-dashed rounded-xl border-muted">
                  <div class="mx-auto mb-3 w-12 h-12 bg-muted rounded-full flex items-center justify-center">
                    <FileUp class="h-6 w-6 text-muted-foreground" />
                  </div>
                  <p class="text-sm font-medium text-muted-foreground mb-1">No file uploaded</p>
                  <p class="text-xs text-muted-foreground">
                    Select a CSV file to import students
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