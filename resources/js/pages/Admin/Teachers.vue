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
interface Teacher {
  id: number;
  employee_id: string;
  name: string;
  email: string;
  phone: string;
  department: string;
  position: string;
  is_active: boolean;
  created_at: string;
  classes_count: number;
}

interface Props {
  teachers: Teacher[];
}

const props = defineProps<Props>();
const searchQuery = ref('');

// Dialog states
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const editingTeacher = ref<Teacher | null>(null);

// Create teacher form
const createForm = useForm({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  employee_id: '',
  department: '',
  position: '',
  password: '',
  password_confirmation: '',
});

// Edit teacher form
const editForm = useForm({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  employee_id: '',
  department: '',
  position: '',
});

// Functions
const openCreateDialog = () => {
  createForm.reset();
  showCreateDialog.value = true;
};

const openEditDialog = (teacher: Teacher) => {
  editingTeacher.value = teacher;
  const names = teacher.name.split(' ');
  editForm.first_name = names[0] || '';
  editForm.last_name = names.slice(1).join(' ') || '';
  editForm.email = teacher.email;
  editForm.phone = teacher.phone;
  editForm.employee_id = teacher.employee_id;
  editForm.department = teacher.department;
  editForm.position = teacher.position;
  showEditDialog.value = true;
};

const createTeacher = () => {
  createForm.post('/admin/teachers', {
    onSuccess: () => {
      showCreateDialog.value = false;
      createForm.reset();
    },
    onError: (errors) => {
      console.error('Create teacher errors:', errors);
      // Errors will be automatically displayed in the form
    }
  });
};

const updateTeacher = () => {
  if (!editingTeacher.value) return;
  
  editForm.put(`/admin/teachers/${editingTeacher.value.id}`, {
    onSuccess: () => {
      showEditDialog.value = false;
      editingTeacher.value = null;
    },
  });
};

const toggleTeacherStatus = (teacher: Teacher) => {
  const action = teacher.is_active ? 'deactivate' : 'activate';
  const message = teacher.is_active 
    ? `Are you sure you want to deactivate ${teacher.name}? They will no longer be able to access the system.`
    : `Are you sure you want to activate ${teacher.name}? They will regain access to the system.`;
    
  if (confirm(message)) {
    useForm({}).patch(`/admin/teachers/${teacher.id}/toggle-status`);
  }
};

const deleteTeacher = (teacher: Teacher) => {
  if (confirm(`Are you sure you want to delete ${teacher.name}? This action cannot be undone.`)) {
    useForm({}).delete(`/admin/teachers/${teacher.id}`);
  }
};

// Breadcrumb data
const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Teachers', href: '#' }
];

// Filter teachers based on search
const filteredTeachers = computed(() => {
  if (!searchQuery.value) return props.teachers;
  
  return props.teachers.filter(teacher =>
    teacher.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    teacher.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    teacher.department.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});
</script>

<template>
  <Head title="Teachers Management" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-4 p-4 pt-4">
      <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Teachers Management</h1>
          <p class="text-muted-foreground">Manage teacher accounts and permissions</p>
        </div>
        <Button @click="openCreateDialog">
          <Plus class="h-4 w-4 mr-2" />
          Add Teacher
        </Button>
      </div>

      <!-- Stats Cards -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Teachers</CardTitle>
            <UserCheck class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ teachers.length }}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Teachers</CardTitle>
            <UserCheck class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ teachers.filter(t => t.is_active).length }}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Inactive Teachers</CardTitle>
            <UserX class="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ teachers.filter(t => !t.is_active).length }}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Classes</CardTitle>
            <UserCheck class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ teachers.reduce((sum, t) => sum + t.classes_count, 0) }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Teachers Table -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>Teachers List</CardTitle>
              <CardDescription>Manage all teacher accounts in the system</CardDescription>
            </div>
            <div class="flex items-center space-x-2">
              <div class="relative">
                <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  v-model="searchQuery"
                  placeholder="Search teachers..."
                  class="pl-8 w-64"
                />
              </div>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Employee ID</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Department</TableHead>
                <TableHead>Position</TableHead>
                <TableHead>Classes</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="teacher in filteredTeachers" :key="teacher.id">
                <TableCell class="font-medium">{{ teacher.employee_id }}</TableCell>
                <TableCell>{{ teacher.name }}</TableCell>
                <TableCell>{{ teacher.email }}</TableCell>
                <TableCell>{{ teacher.department }}</TableCell>
                <TableCell>{{ teacher.position }}</TableCell>
                <TableCell>{{ teacher.classes_count }}</TableCell>
                <TableCell>
                  <Badge :variant="teacher.is_active ? 'default' : 'secondary'">
                    {{ teacher.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <div class="flex items-center space-x-2">
                    <Button variant="outline" size="sm" @click="openEditDialog(teacher)">
                      <Edit class="h-4 w-4" />
                    </Button>
                    <Button 
                      size="sm" 
                      :variant="teacher.is_active ? 'destructive' : 'default'"
                      @click="toggleTeacherStatus(teacher)"
                    >
                      <UserX v-if="teacher.is_active" class="h-4 w-4" />
                      <UserCheck v-else class="h-4 w-4" />
                    </Button>
                    <Button variant="destructive" size="sm" @click="deleteTeacher(teacher)">
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
      </div>
    </div>

    <!-- Create Teacher Dialog -->
    <Dialog v-model:open="showCreateDialog">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Add New Teacher</DialogTitle>
          <DialogDescription>
            Create a new teacher account. They will receive login credentials via email.
          </DialogDescription>
        </DialogHeader>
        
        <!-- General Error Display -->
        <div v-if="createForm.errors && Object.keys(createForm.errors).length > 0" class="bg-red-50 border border-red-200 rounded-md p-3 mb-4">
          <div class="text-sm text-red-800">
            <p class="font-medium">Please correct the following errors:</p>
            <ul class="mt-2 list-disc list-inside">
              <li v-for="(error, field) in createForm.errors" :key="field">{{ error }}</li>
            </ul>
          </div>
        </div>
        
        <form @submit.prevent="createTeacher" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="create-first-name">First Name *</Label>
              <Input 
                id="create-first-name" 
                v-model="createForm.first_name" 
                :class="{ 'border-red-500': createForm.errors.first_name }"
                required 
              />
              <p v-if="createForm.errors.first_name" class="text-sm text-red-500">{{ createForm.errors.first_name }}</p>
            </div>
            <div class="space-y-2">
              <Label for="create-last-name">Last Name *</Label>
              <Input 
                id="create-last-name" 
                v-model="createForm.last_name" 
                :class="{ 'border-red-500': createForm.errors.last_name }"
                required 
              />
              <p v-if="createForm.errors.last_name" class="text-sm text-red-500">{{ createForm.errors.last_name }}</p>
            </div>
          </div>
          <div class="space-y-2">
            <Label for="create-email">Email *</Label>
            <Input 
              id="create-email" 
              v-model="createForm.email" 
              type="email" 
              :class="{ 'border-red-500': createForm.errors.email }"
              required 
            />
            <p v-if="createForm.errors.email" class="text-sm text-red-500">{{ createForm.errors.email }}</p>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="create-employee-id">Employee ID *</Label>
              <Input 
                id="create-employee-id" 
                v-model="createForm.employee_id" 
                :class="{ 'border-red-500': createForm.errors.employee_id }"
                required 
              />
              <p v-if="createForm.errors.employee_id" class="text-sm text-red-500">{{ createForm.errors.employee_id }}</p>
            </div>
            <div class="space-y-2">
              <Label for="create-phone">Phone</Label>
              <Input 
                id="create-phone" 
                v-model="createForm.phone" 
                :class="{ 'border-red-500': createForm.errors.phone }"
              />
              <p v-if="createForm.errors.phone" class="text-sm text-red-500">{{ createForm.errors.phone }}</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="create-department">Department *</Label>
              <Input 
                id="create-department" 
                v-model="createForm.department" 
                :class="{ 'border-red-500': createForm.errors.department }"
                required 
              />
              <p v-if="createForm.errors.department" class="text-sm text-red-500">{{ createForm.errors.department }}</p>
            </div>
            <div class="space-y-2">
              <Label for="create-position">Position *</Label>
              <Input 
                id="create-position" 
                v-model="createForm.position" 
                :class="{ 'border-red-500': createForm.errors.position }"
                required 
              />
              <p v-if="createForm.errors.position" class="text-sm text-red-500">{{ createForm.errors.position }}</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="create-password">Password *</Label>
              <Input 
                id="create-password" 
                v-model="createForm.password" 
                type="password" 
                :class="{ 'border-red-500': createForm.errors.password }"
                required 
              />
              <p v-if="createForm.errors.password" class="text-sm text-red-500">{{ createForm.errors.password }}</p>
            </div>
            <div class="space-y-2">
              <Label for="create-password-confirmation">Confirm Password *</Label>
              <Input 
                id="create-password-confirmation" 
                v-model="createForm.password_confirmation" 
                type="password" 
                required 
              />
            </div>
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="showCreateDialog = false">Cancel</Button>
            <Button type="submit" :disabled="createForm.processing">
              {{ createForm.processing ? 'Creating...' : 'Create Teacher' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Teacher Dialog -->
    <Dialog v-model:open="showEditDialog">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Edit Teacher</DialogTitle>
          <DialogDescription>
            Update teacher information.
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="updateTeacher" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="edit-first-name">First Name *</Label>
              <Input 
                id="edit-first-name" 
                v-model="editForm.first_name" 
                :class="{ 'border-red-500': editForm.errors.first_name }"
                required 
              />
              <p v-if="editForm.errors.first_name" class="text-sm text-red-500">{{ editForm.errors.first_name }}</p>
            </div>
            <div class="space-y-2">
              <Label for="edit-last-name">Last Name *</Label>
              <Input 
                id="edit-last-name" 
                v-model="editForm.last_name" 
                :class="{ 'border-red-500': editForm.errors.last_name }"
                required 
              />
              <p v-if="editForm.errors.last_name" class="text-sm text-red-500">{{ editForm.errors.last_name }}</p>
            </div>
          </div>
          <div class="space-y-2">
            <Label for="edit-email">Email *</Label>
            <Input 
              id="edit-email" 
              v-model="editForm.email" 
              type="email" 
              :class="{ 'border-red-500': editForm.errors.email }"
              required 
            />
            <p v-if="editForm.errors.email" class="text-sm text-red-500">{{ editForm.errors.email }}</p>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="edit-employee-id">Employee ID *</Label>
              <Input 
                id="edit-employee-id" 
                v-model="editForm.employee_id" 
                :class="{ 'border-red-500': editForm.errors.employee_id }"
                required 
              />
              <p v-if="editForm.errors.employee_id" class="text-sm text-red-500">{{ editForm.errors.employee_id }}</p>
            </div>
            <div class="space-y-2">
              <Label for="edit-phone">Phone</Label>
              <Input 
                id="edit-phone" 
                v-model="editForm.phone" 
                :class="{ 'border-red-500': editForm.errors.phone }"
              />
              <p v-if="editForm.errors.phone" class="text-sm text-red-500">{{ editForm.errors.phone }}</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="edit-department">Department *</Label>
              <Input 
                id="edit-department" 
                v-model="editForm.department" 
                :class="{ 'border-red-500': editForm.errors.department }"
                required 
              />
              <p v-if="editForm.errors.department" class="text-sm text-red-500">{{ editForm.errors.department }}</p>
            </div>
            <div class="space-y-2">
              <Label for="edit-position">Position *</Label>
              <Input 
                id="edit-position" 
                v-model="editForm.position" 
                :class="{ 'border-red-500': editForm.errors.position }"
                required 
              />
              <p v-if="editForm.errors.position" class="text-sm text-red-500">{{ editForm.errors.position }}</p>
            </div>
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="showEditDialog = false">Cancel</Button>
            <Button type="submit" :disabled="editForm.processing">
              {{ editForm.processing ? 'Updating...' : 'Update Teacher' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>