<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Separator } from '@/components/ui/separator';
import { Settings, School, Shield, Clock } from 'lucide-vue-next';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

// Props from controller
interface SchoolInfo {
  name: string;
  address: string;
  phone: string;
  email: string;
}

interface AuthenticationSettings {
  require_email_verification: boolean;
  password_min_length: number;
  session_timeout: number;
}

interface AttendanceSettings {
  auto_mark_absent_after: number;
  allow_late_submissions: boolean;
  require_excuse_for_absence: boolean;
}

interface Props {
  settings: {
    school_info: SchoolInfo;
    authentication: AuthenticationSettings;
    attendance: AttendanceSettings;
  };
}

const props = defineProps<Props>();

// Reactive form data
const schoolInfo = ref({ ...props.settings.school_info });
const authSettings = ref({ ...props.settings.authentication });
const attendanceSettings = ref({ ...props.settings.attendance });

// Form for saving settings
const settingsForm = useForm({
  school_info: { ...props.settings.school_info },
  authentication: { ...props.settings.authentication },
  attendance: { ...props.settings.attendance },
});

// Update forms when reactive data changes
const updateForms = () => {
  settingsForm.school_info = { ...schoolInfo.value };
  settingsForm.authentication = { ...authSettings.value };
  settingsForm.attendance = { ...attendanceSettings.value };
};

// Save settings
const saveSettings = () => {
  updateForms();
  settingsForm.put('/admin/settings', {
    onSuccess: () => {
      // Settings saved successfully
    },
  });
};

// Breadcrumb data
const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Settings', href: '#' }
];
</script>

<template>
  <Head title="System Settings" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-4 p-4 pt-4">
      <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">System Settings</h1>
          <p class="text-muted-foreground">Configure school information and system preferences</p>
        </div>
        <Button @click="saveSettings" :disabled="settingsForm.processing">
          <span v-if="settingsForm.processing">Saving...</span>
          <span v-else>Save Changes</span>
        </Button>
      </div>

      <!-- School Information -->
      <Card>
        <CardHeader>
          <div class="flex items-center space-x-2">
            <School class="h-5 w-5" />
            <CardTitle>School Information</CardTitle>
          </div>
          <CardDescription>Basic information about your educational institution</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="school-name">School Name</Label>
              <Input id="school-name" v-model="schoolInfo.name" />
            </div>
            <div class="space-y-2">
              <Label for="school-email">Email Address</Label>
              <Input id="school-email" v-model="schoolInfo.email" type="email" />
            </div>
          </div>
          <div class="space-y-2">
            <Label for="school-address">Address</Label>
            <Input id="school-address" v-model="schoolInfo.address" />
          </div>
          <div class="space-y-2">
            <Label for="school-phone">Phone Number</Label>
            <Input id="school-phone" v-model="schoolInfo.phone" />
          </div>
        </CardContent>
      </Card>

      <!-- Authentication Settings -->
      <Card>
        <CardHeader>
          <div class="flex items-center space-x-2">
            <Shield class="h-5 w-5" />
            <CardTitle>Authentication & Security</CardTitle>
          </div>
          <CardDescription>Configure login and security requirements</CardDescription>
        </CardHeader>
        <CardContent class="space-y-6">
          <div class="flex items-center justify-between">
            <div class="space-y-0.5">
              <Label>Require Email Verification</Label>
              <p class="text-sm text-muted-foreground">Users must verify their email before accessing the system</p>
            </div>
            <Checkbox v-model:checked="authSettings.require_email_verification" />
          </div>
          
          <Separator />
          
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="password-length">Minimum Password Length</Label>
              <Input 
                id="password-length" 
                v-model.number="authSettings.password_min_length" 
                type="number" 
                min="6" 
                max="20" 
              />
            </div>
            <div class="space-y-2">
              <Label for="session-timeout">Session Timeout (minutes)</Label>
              <Input 
                id="session-timeout" 
                v-model.number="authSettings.session_timeout" 
                type="number" 
                min="30" 
                max="480" 
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Attendance Settings -->
      <Card>
        <CardHeader>
          <div class="flex items-center space-x-2">
            <Clock class="h-5 w-5" />
            <CardTitle>Attendance Rules</CardTitle>
          </div>
          <CardDescription>Configure attendance tracking and policies</CardDescription>
        </CardHeader>
        <CardContent class="space-y-6">
          <div class="space-y-2">
            <Label for="absent-after">Auto-mark Absent After (minutes)</Label>
            <Input 
              id="absent-after" 
              v-model.number="attendanceSettings.auto_mark_absent_after" 
              type="number" 
              min="5" 
              max="60" 
            />
            <p class="text-sm text-muted-foreground">Students will be marked absent if they don't check in within this time</p>
          </div>
          
          <Separator />
          
          <div class="flex items-center justify-between">
            <div class="space-y-0.5">
              <Label>Allow Late Submissions</Label>
              <p class="text-sm text-muted-foreground">Students can mark attendance after the session has ended</p>
            </div>
            <Checkbox v-model:checked="attendanceSettings.allow_late_submissions" />
          </div>
          
          <Separator />
          
          <div class="flex items-center justify-between">
            <div class="space-y-0.5">
              <Label>Require Excuse for Absence</Label>
              <p class="text-sm text-muted-foreground">Students must provide a reason when marked absent</p>
            </div>
            <Checkbox v-model:checked="attendanceSettings.require_excuse_for_absence" />
          </div>
        </CardContent>
      </Card>

      <!-- Role Permissions -->
      <Card>
        <CardHeader>
          <div class="flex items-center space-x-2">
            <Settings class="h-5 w-5" />
            <CardTitle>Role Permissions</CardTitle>
          </div>
          <CardDescription>Configure what different user roles can access</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div class="p-4 border rounded-lg">
              <h4 class="font-semibold mb-2">Administrator</h4>
              <p class="text-sm text-muted-foreground mb-3">Full system access including user management and settings</p>
              <div class="text-sm">
                <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded mr-2 mb-1">Teachers Management</span>
                <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded mr-2 mb-1">System Settings</span>
                <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded mr-2 mb-1">School-wide Reports</span>
              </div>
            </div>
            
            <div class="p-4 border rounded-lg">
              <h4 class="font-semibold mb-2">Teacher</h4>
              <p class="text-sm text-muted-foreground mb-3">Manage their classes, students, and attendance</p>
              <div class="text-sm">
                <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded mr-2 mb-1">Class Management</span>
                <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded mr-2 mb-1">Attendance Tracking</span>
                <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded mr-2 mb-1">Student Reports</span>
                <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded mr-2 mb-1">File Management</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
      </div>
    </div>
  </AppLayout>
</template>