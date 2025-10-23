<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Search, Plus, Edit, Trash2, UserCheck, UserX } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';

// Props from controller
interface Student {
  id: number;
  student_id: string;
  name: string;
  email: string;
  phone: string;
  year: string;
  course: string;
  section: string;
  is_active: boolean;
  created_at: string;
  classes_count: number;
}

interface Props {
  students: Student[];
}

const props = defineProps<Props>();
const searchQuery = ref('');

// Dialog states
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const editingStudent = ref<Student | null>(null);

// Create student form
const createForm = useForm({
  name: '',
  email: '',
  phone: '',
  student_id: '',
  year: '',
  course: '',
  section: '',
  password: '',
  password_confirmation: '',
});

// Edit student form
const editForm = useForm({
  name: '',
  email: '',
  phone: '',
  student_id: '',
  year: '',
  course: '',
  section: '',
});

// Filtered students based on search
const filteredStudents = computed(() => {
  if (!searchQuery.value) return props.students;
  
  const query = searchQuery.value.toLowerCase();
  return props.students.filter(student => 
    student.name.toLowerCase().includes(query) ||
    student.email.toLowerCase().includes(query) ||
    student.student_id.toLowerCase().includes(query) ||
    student.course.toLowerCase().includes(query) ||
    student.section.toLowerCase().includes(query)
  );
});

// Statistics
const totalStudents = computed(() => props.students.length);
const activeStudents = computed(() => props.students.filter(s => s.is_active).length);
const inactiveStudents = computed(() => props.students.filter(s => !s.is_active).length);

// Methods
const createStudent = () => {
  console.log('Creating student with data:', createForm.data());
  console.log('Form errors before submit:', createForm.errors);
  
  createForm.post('/admin/students', {
    onSuccess: () => {
      console.log('Student created successfully!');
      showCreateDialog.value = false;
      createForm.reset();
    },
    onError: (errors) => {
      console.error('Failed to create student. Errors:', errors);
      console.error('Form errors:', createForm.errors);
    },
    onFinish: () => {
      console.log('Create request finished');
    }
  });
};

const openEditDialog = (student: Student) => {
  editingStudent.value = student;
  editForm.name = student.name;
  editForm.email = student.email;
  editForm.phone = student.phone || '';
  editForm.student_id = student.student_id;
  editForm.year = student.year;
  editForm.course = student.course;
  editForm.section = student.section;
  showEditDialog.value = true;
};

const updateStudent = () => {
  if (!editingStudent.value) return;
  
  console.log('Updating student ID:', editingStudent.value.id);
  console.log('Update data:', editForm.data());
  
  editForm.put(`/admin/students/${editingStudent.value.id}`, {
    onSuccess: () => {
      console.log('Student updated successfully!');
      showEditDialog.value = false;
      editingStudent.value = null;
      editForm.reset();
    },
    onError: (errors) => {
      console.error('Failed to update student. Errors:', errors);
      console.error('Form errors:', editForm.errors);
    }
  });
};

const toggleStudentStatus = (studentId: number) => {
  console.log('Toggling status for student ID:', studentId);
  useForm({}).patch(`/admin/students/${studentId}/toggle-status`, {
    onSuccess: () => {
      console.log('Student status toggled successfully!');
    },
    onError: (errors) => {
      console.error('Failed to toggle status. Errors:', errors);
    }
  });
};

const deleteStudent = (studentId: number, studentName: string) => {
  console.log('Attempting to delete student:', studentId, studentName);
  
  if (confirm(`Are you sure you want to deactivate ${studentName}? This action will preserve their data but prevent them from accessing the system.`)) {
    console.log('Delete confirmed, sending request...');
    useForm({}).delete(`/admin/students/${studentId}`, {
      onSuccess: () => {
        console.log('Student deleted successfully!');
      },
      onError: (errors) => {
        console.error('Failed to delete student. Errors:', errors);
      },
      onFinish: () => {
        console.log('Delete request finished');
      }
    });
  } else {
    console.log('Delete cancelled by user');
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const breadcrumbs = [
  { title: 'Admin Dashboard', href: '/dashboard' },
  { title: 'Students', href: '/admin/students' }
];
</script>

<template>
  <Head title="Students Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Students Management</h1>
          <p class="text-muted-foreground">Manage student accounts and information</p>
        </div>
        
        <Dialog v-model:open="showCreateDialog">
          <DialogTrigger as-child>
            <Button class="flex items-center gap-2">
              <Plus class="h-4 w-4" />
              Add Student
            </Button>
          </DialogTrigger>
          <DialogContent class="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>Add New Student</DialogTitle>
              <DialogDescription>Create a new student account with access credentials.</DialogDescription>
            </DialogHeader>
            <form @submit.prevent="createStudent" class="space-y-4">
              <div class="grid grid-cols-1 gap-4">
                <div class="grid gap-2">
                  <Label for="create_name">Full Name</Label>
                  <Input
                    id="create_name"
                    v-model="createForm.name"
                    placeholder="Enter full name"
                    :class="{ 'border-red-500': createForm.errors.name }"
                    required
                  />
                  <span v-if="createForm.errors.name" class="text-sm text-red-500">{{ createForm.errors.name }}</span>
                </div>
                
                <div class="grid gap-2">
                  <Label for="create_student_id">Student ID</Label>
                  <Input
                    id="create_student_id"
                    v-model="createForm.student_id"
                    placeholder="e.g., 23-62514"
                    :class="{ 'border-red-500': createForm.errors.student_id }"
                    required
                  />
                  <span v-if="createForm.errors.student_id" class="text-sm text-red-500">{{ createForm.errors.student_id }}</span>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                  <div class="grid gap-2">
                    <Label for="create_course">Course</Label>
                    <Input
                      id="create_course"
                      v-model="createForm.course"
                      placeholder="e.g., BSCS"
                      :class="{ 'border-red-500': createForm.errors.course }"
                      required
                    />
                    <span v-if="createForm.errors.course" class="text-sm text-red-500">{{ createForm.errors.course }}</span>
                  </div>
                  <div class="grid gap-2">
                    <Label for="create_section">Section</Label>
                    <Input
                      id="create_section"
                      v-model="createForm.section"
                      placeholder="e.g., A"
                      :class="{ 'border-red-500': createForm.errors.section }"
                      required
                    />
                    <span v-if="createForm.errors.section" class="text-sm text-red-500">{{ createForm.errors.section }}</span>
                  </div>
                </div>
                
                <div class="grid gap-2">
                  <Label for="create_year">Year Level</Label>
                  <Input
                    id="create_year"
                    v-model="createForm.year"
                    placeholder="e.g., 4th Year"
                    :class="{ 'border-red-500': createForm.errors.year }"
                    required
                  />
                  <span v-if="createForm.errors.year" class="text-sm text-red-500">{{ createForm.errors.year }}</span>
                </div>
                
                <div class="grid gap-2">
                  <Label for="create_email">Email</Label>
                  <Input
                    id="create_email"
                    type="email"
                    v-model="createForm.email"
                    placeholder="student@university.edu"
                    :class="{ 'border-red-500': createForm.errors.email }"
                    required
                  />
                  <span v-if="createForm.errors.email" class="text-sm text-red-500">{{ createForm.errors.email }}</span>
                </div>
                
                <div class="grid gap-2">
                  <Label for="create_phone">Phone (Optional)</Label>
                  <Input
                    id="create_phone"
                    v-model="createForm.phone"
                    placeholder="+1 (555) 123-4567"
                    :class="{ 'border-red-500': createForm.errors.phone }"
                  />
                  <span v-if="createForm.errors.phone" class="text-sm text-red-500">{{ createForm.errors.phone }}</span>
                </div>
                
                <div class="grid gap-2">
                  <Label for="create_password">Password</Label>
                  <Input
                    id="create_password"
                    type="password"
                    v-model="createForm.password"
                    placeholder="Enter secure password"
                    :class="{ 'border-red-500': createForm.errors.password }"
                    required
                  />
                  <span v-if="createForm.errors.password" class="text-sm text-red-500">{{ createForm.errors.password }}</span>
                </div>
                
                <div class="grid gap-2">
                  <Label for="create_password_confirmation">Confirm Password</Label>
                  <Input
                    id="create_password_confirmation"
                    type="password"
                    v-model="createForm.password_confirmation"
                    placeholder="Confirm password"
                    required
                  />
                </div>
              </div>
              
              <DialogFooter>
                <Button type="button" variant="outline" @click="showCreateDialog = false">Cancel</Button>
                <Button type="submit" :disabled="createForm.processing">
                  {{ createForm.processing ? 'Creating...' : 'Create Student' }}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Statistics -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium">Total Students</span>
              <span class="text-xl">👥</span>
            </div>
            <div class="text-2xl font-bold">{{ totalStudents }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Registered in the system
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium">Active Students</span>
              <span class="text-xl">✅</span>
            </div>
            <div class="text-2xl font-bold text-green-600">{{ activeStudents }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Currently active accounts
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium">Inactive Students</span>
              <span class="text-xl">❌</span>
            </div>
            <div class="text-2xl font-bold text-red-600">{{ inactiveStudents }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Deactivated accounts
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Students Table -->
      <Card>
        <CardHeader>
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <CardTitle>Students List</CardTitle>
              <CardDescription>Manage student accounts and their information</CardDescription>
            </div>
            <div class="flex items-center gap-2">
              <div class="relative">
                <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search students..."
                  v-model="searchQuery"
                  class="pl-8 w-64"
                />
              </div>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead class="min-w-[200px]">Student Info</TableHead>
                  <TableHead class="min-w-[150px]">Academic Info</TableHead>
                  <TableHead class="min-w-[200px]">Contact</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Classes</TableHead>
                  <TableHead>Joined</TableHead>
                  <TableHead class="text-right min-w-[120px]">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="student in filteredStudents" :key="student.id" class="hover:bg-muted/50">
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ student.name }}</div>
                      <div class="text-sm text-muted-foreground">{{ student.student_id }}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ student.course }}-{{ student.section }}</div>
                      <div class="text-sm text-muted-foreground">{{ student.year }}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div>
                      <div class="text-sm">{{ student.email }}</div>
                      <div class="text-sm text-muted-foreground">{{ student.phone || 'No phone' }}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge :variant="student.is_active ? 'default' : 'secondary'">
                      {{ student.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">
                      {{ student.classes_count }} classes
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div class="text-sm">{{ formatDate(student.created_at) }}</div>
                  </TableCell>
                  <TableCell class="text-right">
                    <div class="flex items-center justify-end gap-1">
                      <Button
                        size="sm"
                        variant="ghost"
                        @click="openEditDialog(student)"
                        title="Edit Student"
                      >
                        <Edit class="h-4 w-4" />
                      </Button>
                      <Button
                        size="sm"
                        variant="ghost"
                        @click="toggleStudentStatus(student.id)"
                        :title="student.is_active ? 'Deactivate' : 'Activate'"
                      >
                        <UserCheck v-if="student.is_active" class="h-4 w-4 text-green-600" />
                        <UserX v-else class="h-4 w-4 text-red-600" />
                      </Button>
                      <Button
                        size="sm"
                        variant="ghost"
                        @click="deleteStudent(student.id, student.name)"
                        class="text-red-600 hover:text-red-700"
                        title="Delete Student"
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
                <TableRow v-if="filteredStudents.length === 0">
                  <TableCell colspan="7" class="text-center py-8 text-muted-foreground">
                    No students found matching your search criteria.
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      <!-- Edit Student Dialog -->
      <Dialog v-model:open="showEditDialog">
        <DialogContent class="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Edit Student</DialogTitle>
            <DialogDescription>Update student information.</DialogDescription>
          </DialogHeader>
          <form @submit.prevent="updateStudent" class="space-y-4" v-if="editingStudent">
            <div class="grid grid-cols-1 gap-4">
              <div class="grid gap-2">
                <Label for="edit_name">Full Name</Label>
                <Input
                  id="edit_name"
                  v-model="editForm.name"
                  :class="{ 'border-red-500': editForm.errors.name }"
                  required
                />
                <span v-if="editForm.errors.name" class="text-sm text-red-500">{{ editForm.errors.name }}</span>
              </div>
              
              <div class="grid gap-2">
                <Label for="edit_student_id">Student ID</Label>
                <Input
                  id="edit_student_id"
                  v-model="editForm.student_id"
                  :class="{ 'border-red-500': editForm.errors.student_id }"
                  required
                />
                <span v-if="editForm.errors.student_id" class="text-sm text-red-500">{{ editForm.errors.student_id }}</span>
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                  <Label for="edit_course">Course</Label>
                  <Input
                    id="edit_course"
                    v-model="editForm.course"
                    :class="{ 'border-red-500': editForm.errors.course }"
                    required
                  />
                  <span v-if="editForm.errors.course" class="text-sm text-red-500">{{ editForm.errors.course }}</span>
                </div>
                <div class="grid gap-2">
                  <Label for="edit_section">Section</Label>
                  <Input
                    id="edit_section"
                    v-model="editForm.section"
                    :class="{ 'border-red-500': editForm.errors.section }"
                    required
                  />
                  <span v-if="editForm.errors.section" class="text-sm text-red-500">{{ editForm.errors.section }}</span>
                </div>
              </div>
              
              <div class="grid gap-2">
                <Label for="edit_year">Year Level</Label>
                <Input
                  id="edit_year"
                  v-model="editForm.year"
                  :class="{ 'border-red-500': editForm.errors.year }"
                  required
                />
                <span v-if="editForm.errors.year" class="text-sm text-red-500">{{ editForm.errors.year }}</span>
              </div>
              
              <div class="grid gap-2">
                <Label for="edit_email">Email</Label>
                <Input
                  id="edit_email"
                  type="email"
                  v-model="editForm.email"
                  :class="{ 'border-red-500': editForm.errors.email }"
                  required
                />
                <span v-if="editForm.errors.email" class="text-sm text-red-500">{{ editForm.errors.email }}</span>
              </div>
              
              <div class="grid gap-2">
                <Label for="edit_phone">Phone</Label>
                <Input
                  id="edit_phone"
                  v-model="editForm.phone"
                  :class="{ 'border-red-500': editForm.errors.phone }"
                />
                <span v-if="editForm.errors.phone" class="text-sm text-red-500">{{ editForm.errors.phone }}</span>
              </div>
            </div>
            
            <DialogFooter>
              <Button type="button" variant="outline" @click="showEditDialog = false">Cancel</Button>
              <Button type="submit" :disabled="editForm.processing">
                {{ editForm.processing ? 'Updating...' : 'Update Student' }}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>