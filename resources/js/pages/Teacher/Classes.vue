<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import { Plus, Edit, Users, Clock, Calendar } from 'lucide-vue-next';

// Props from controller
interface ClassItem {
  id: number;
  name: string;
  course: string;
  section: string;
  year: string;
  schedule_time: string;
  schedule_days: string[];
  student_count?: number;
  is_active: boolean;
}

interface Props {
  teacher: {
    id: number;
    first_name: string;
    last_name: string;
    department: string;
    position: string;
    email: string;
  };
  classes: ClassItem[];
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
  { title: 'Classes', href: '/teacher/classes' }
];

// Dialog states
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const editingClass = ref<ClassItem | null>(null);

// Create class form
const createForm = useForm({
  name: '',
  class_code: '',
  section: '',
  subject: '',
  course: '',
  year: '',
  description: '',
  room: '',
  schedule_time: '',
  schedule_days: [] as string[],
  academic_year: '2024-2025',
  semester: 'Fall'
});

// Edit class form
const editForm = useForm({
  name: '',
  class_code: '',
  section: '',
  subject: '',
  course: '',
  year: '',
  description: '',
  room: '',
  schedule_time: '',
  schedule_days: [] as string[],
  academic_year: '2024-2025',
  semester: 'Fall'
});

// Functions
const openCreateDialog = () => {
  createForm.reset();
  showCreateDialog.value = true;
};

const openEditDialog = (classItem: ClassItem) => {
  editingClass.value = classItem;
  editForm.name = classItem.name;
  editForm.course = classItem.course;
  editForm.section = classItem.section;
  editForm.year = classItem.year;
  editForm.schedule_time = classItem.schedule_time;
  editForm.schedule_days = classItem.schedule_days || [];
  showEditDialog.value = true;
};

const createClass = () => {
  createForm.post('/teacher/classes', {
    onSuccess: () => {
      showCreateDialog.value = false;
      createForm.reset();
    },
  });
};

const updateClass = () => {
  if (!editingClass.value) return;
  
  editForm.put(`/teacher/classes/${editingClass.value.id}`, {
    onSuccess: () => {
      showEditDialog.value = false;
      editingClass.value = null;
    },
  });
};

// Mock data for missing fields
const mockClassData = (classItem: ClassItem) => ({
  ...classItem,
  class_code: `${classItem.course}${classItem.id}`,
  subject: classItem.name,
  room: `Room ${100 + classItem.id}`,
  student_count: classItem.student_count || Math.floor(Math.random() * 30) + 10
});
</script>

<template>
  <Head title="Teacher Classes" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">
      
      <!-- Header Section -->
      <div class="flex items-center justify-between">
        <div class="space-y-2">
          <h1 class="text-3xl font-bold tracking-tight">Class Management</h1>
          <p class="text-muted-foreground">
            Manage your classes, students, and class materials
          </p>
        </div>
        <Button @click="openCreateDialog">
          <Plus class="h-4 w-4 mr-2" />
          Create New Class
        </Button>
      </div>

      <!-- Quick Stats Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Classes</CardTitle>
            <span class="text-xl">📚</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ classes.length }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Active classes
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Students</CardTitle>
            <span class="text-xl">👥</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ classes.reduce((sum, c) => sum + (c.student_count || 25), 0) }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Across all classes
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Classes</CardTitle>
            <span class="text-xl">🟢</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ classes.filter(c => c.is_active).length }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Currently active
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">This Week</CardTitle>
            <span class="text-xl">📅</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ classes.length * 3 }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Total sessions
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Classes List -->
      <Card>
        <CardHeader>
          <CardTitle>Your Classes</CardTitle>
          <CardDescription>Manage and view details of your classes</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="classes.length === 0" class="text-center py-8">
            <div class="text-muted-foreground">
              <span class="text-4xl mb-4 block">📚</span>
              <p class="text-lg">No classes yet</p>
              <p class="text-sm">Create your first class to get started</p>
            </div>
          </div>
          
          <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Card v-for="classItem in classes" :key="classItem.id" class="hover:shadow-md transition-shadow">
              <CardContent class="p-4">
                <div class="space-y-3">
                  <!-- Class Header -->
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <h3 class="font-semibold text-lg">{{ classItem.name }}</h3>
                      <p class="text-sm text-muted-foreground">{{ classItem.course }} - {{ classItem.section }}</p>
                      <p class="text-sm text-muted-foreground">{{ classItem.year }}</p>
                    </div>
                    <Badge :variant="classItem.is_active ? 'default' : 'secondary'">
                      {{ classItem.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                  </div>

                  <!-- Class Details -->
                  <Separator />
                  
                  <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                      <span class="text-muted-foreground">Schedule:</span>
                      <span>{{ classItem.schedule_time }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-muted-foreground">Days:</span>
                      <span>{{ Array.isArray(classItem.schedule_days) ? classItem.schedule_days.join(', ') : classItem.schedule_days }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-muted-foreground">Students:</span>
                      <span>{{ classItem.student_count || 25 }}</span>
                    </div>
                  </div>

                  <!-- Action Buttons -->
                  <Separator />
                  
                  <div class="flex gap-2">
                    <Button size="sm" variant="default" @click="$inertia.visit(`/teacher/classes/${classItem.id}`)">
                      <Users class="h-4 w-4 mr-1" />
                      Students
                    </Button>
                    <Button size="sm" variant="outline" @click="$inertia.visit(`/teacher/attendance/direct/${classItem.id}`)">
                      <Clock class="h-4 w-4 mr-1" />
                      Take Attendance
                    </Button>
                    <Button size="sm" variant="outline" @click="openEditDialog(classItem)">
                      <Edit class="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </CardContent>
      </Card>

    </div>

    <!-- Create Class Dialog -->
    <Dialog v-model:open="showCreateDialog">
      <DialogContent class="sm:max-w-[600px]">
        <DialogHeader>
          <DialogTitle>Create New Class</DialogTitle>
          <DialogDescription>
            Add a new class to your teaching schedule.
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="createClass" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="create-name">Class Name *</Label>
              <Input 
                id="create-name" 
                v-model="createForm.name" 
                placeholder="e.g., Data Structures and Algorithms"
                :class="{ 'border-red-500': createForm.errors.name }"
                required 
              />
              <p v-if="createForm.errors.name" class="text-sm text-red-500">{{ createForm.errors.name }}</p>
            </div>
            <div class="space-y-2">
              <Label for="create-class-code">Class Code *</Label>
              <Input 
                id="create-class-code" 
                v-model="createForm.class_code" 
                placeholder="e.g., CS101"
                :class="{ 'border-red-500': createForm.errors.class_code }"
                required 
              />
              <p v-if="createForm.errors.class_code" class="text-sm text-red-500">{{ createForm.errors.class_code }}</p>
            </div>
          </div>
          
          <div class="grid grid-cols-3 gap-4">
            <div class="space-y-2">
              <Label for="create-course">Course *</Label>
              <Input 
                id="create-course" 
                v-model="createForm.course" 
                placeholder="e.g., BSCS"
                :class="{ 'border-red-500': createForm.errors.course }"
                required 
              />
              <p v-if="createForm.errors.course" class="text-sm text-red-500">{{ createForm.errors.course }}</p>
            </div>
            <div class="space-y-2">
              <Label for="create-section">Section *</Label>
              <Input 
                id="create-section" 
                v-model="createForm.section" 
                placeholder="e.g., A"
                :class="{ 'border-red-500': createForm.errors.section }"
                required 
              />
              <p v-if="createForm.errors.section" class="text-sm text-red-500">{{ createForm.errors.section }}</p>
            </div>
            <div class="space-y-2">
              <Label for="create-year">Year Level *</Label>
              <Input 
                id="create-year" 
                v-model="createForm.year" 
                placeholder="e.g., 1st Year"
                :class="{ 'border-red-500': createForm.errors.year }"
                required 
              />
              <p v-if="createForm.errors.year" class="text-sm text-red-500">{{ createForm.errors.year }}</p>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="create-room">Room</Label>
              <Input 
                id="create-room" 
                v-model="createForm.room" 
                placeholder="e.g., Room 101"
                :class="{ 'border-red-500': createForm.errors.room }"
              />
              <p v-if="createForm.errors.room" class="text-sm text-red-500">{{ createForm.errors.room }}</p>
            </div>
            <div class="space-y-2">
              <Label for="create-schedule-time">Schedule Time</Label>
              <Input 
                id="create-schedule-time" 
                v-model="createForm.schedule_time" 
                placeholder="e.g., 08:00-10:00"
                :class="{ 'border-red-500': createForm.errors.schedule_time }"
              />
              <p v-if="createForm.errors.schedule_time" class="text-sm text-red-500">{{ createForm.errors.schedule_time }}</p>
            </div>
          </div>
          
          <div class="space-y-2">
            <Label for="create-description">Description</Label>
            <Textarea 
              id="create-description" 
              v-model="createForm.description" 
              placeholder="Brief description of the class"
              :class="{ 'border-red-500': createForm.errors.description }"
            />
            <p v-if="createForm.errors.description" class="text-sm text-red-500">{{ createForm.errors.description }}</p>
          </div>
          
          <DialogFooter>
            <Button type="button" variant="outline" @click="showCreateDialog = false">Cancel</Button>
            <Button type="submit" :disabled="createForm.processing">
              {{ createForm.processing ? 'Creating...' : 'Create Class' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Class Dialog -->
    <Dialog v-model:open="showEditDialog">
      <DialogContent class="sm:max-w-[600px]">
        <DialogHeader>
          <DialogTitle>Edit Class</DialogTitle>
          <DialogDescription>
            Update class information.
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="updateClass" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="edit-name">Class Name *</Label>
              <Input 
                id="edit-name" 
                v-model="editForm.name" 
                :class="{ 'border-red-500': editForm.errors.name }"
                required 
              />
              <p v-if="editForm.errors.name" class="text-sm text-red-500">{{ editForm.errors.name }}</p>
            </div>
            <div class="space-y-2">
              <Label for="edit-class-code">Class Code *</Label>
              <Input 
                id="edit-class-code" 
                v-model="editForm.class_code" 
                :class="{ 'border-red-500': editForm.errors.class_code }"
                required 
              />
              <p v-if="editForm.errors.class_code" class="text-sm text-red-500">{{ editForm.errors.class_code }}</p>
            </div>
          </div>
          
          <div class="grid grid-cols-3 gap-4">
            <div class="space-y-2">
              <Label for="edit-course">Course *</Label>
              <Input 
                id="edit-course" 
                v-model="editForm.course" 
                :class="{ 'border-red-500': editForm.errors.course }"
                required 
              />
              <p v-if="editForm.errors.course" class="text-sm text-red-500">{{ editForm.errors.course }}</p>
            </div>
            <div class="space-y-2">
              <Label for="edit-section">Section *</Label>
              <Input 
                id="edit-section" 
                v-model="editForm.section" 
                :class="{ 'border-red-500': editForm.errors.section }"
                required 
              />
              <p v-if="editForm.errors.section" class="text-sm text-red-500">{{ editForm.errors.section }}</p>
            </div>
            <div class="space-y-2">
              <Label for="edit-year">Year Level *</Label>
              <Input 
                id="edit-year" 
                v-model="editForm.year" 
                :class="{ 'border-red-500': editForm.errors.year }"
                required 
              />
              <p v-if="editForm.errors.year" class="text-sm text-red-500">{{ editForm.errors.year }}</p>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="edit-room">Room</Label>
              <Input 
                id="edit-room" 
                v-model="editForm.room" 
                :class="{ 'border-red-500': editForm.errors.room }"
              />
              <p v-if="editForm.errors.room" class="text-sm text-red-500">{{ editForm.errors.room }}</p>
            </div>
            <div class="space-y-2">
              <Label for="edit-schedule-time">Schedule Time</Label>
              <Input 
                id="edit-schedule-time" 
                v-model="editForm.schedule_time" 
                :class="{ 'border-red-500': editForm.errors.schedule_time }"
              />
              <p v-if="editForm.errors.schedule_time" class="text-sm text-red-500">{{ editForm.errors.schedule_time }}</p>
            </div>
          </div>
          
          <DialogFooter>
            <Button type="button" variant="outline" @click="showEditDialog = false">Cancel</Button>
            <Button type="submit" :disabled="editForm.processing">
              {{ editForm.processing ? 'Updating...' : 'Update Class' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>