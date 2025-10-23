<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Plus, ArrowLeft, Users, Clock, Mail, Phone, UserX } from 'lucide-vue-next';
import AddStudentDialog from '@/components/teacher/AddStudentDialog.vue';

interface Props {
  teacher: {
    id: number;
    first_name: string;
    last_name: string;
    department: string;
    position: string;
    email: string;
  };
  classData: {
    id: number;
    name: string;
    course: string;
    section: string;
    year: string;
    schedule_time: string;
    schedule_days: string[];
  };
  students: Array<{
    id: number;
    student_id: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    year: string;
    course: string;
    section: string;
    status: string;
    enrolled_at: string;
  }>;
  recentSessions: Array<{
    id: number;
    session_name: string;
    session_date: string;
    status: string;
  }>;
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
  { title: 'Classes', href: '/teacher/classes' },
  { title: props.classData.name, href: `/teacher/classes/${props.classData.id}` }
];

// State
const showAddStudentDialog = ref(false);

// Add student form
const addStudentForm = useForm({
  student_id: '',
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  year: props.classData.year,
  course: props.classData.course,
  section: props.classData.section
});

// Functions
const addStudent = () => {
  addStudentForm.post(`/teacher/classes/${props.classData.id}/students`, {
    onSuccess: () => {
      showAddStudentDialog.value = false;
      addStudentForm.reset();
    }
  });
};

const handleStudentAdded = (students: any[]) => {
  showAddStudentDialog.value = false;
  // Refresh the page to show the new students
  window.location.reload();
};

const removeStudent = (studentId: number) => {
  if (confirm('Are you sure you want to remove this student from the class?')) {
    useForm({}).delete(`/teacher/classes/${props.classData.id}/students/${studentId}`);
  }
};

const startAttendance = () => {
  // Navigate to attendance page with this class pre-selected
  window.location.href = `/teacher/attendance?class_id=${props.classData.id}`;
};
</script>

<template>
  <Head :title="`${props.classData.name} - Class Details`" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">
      
      <!-- Header Section -->
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <Button variant="outline" size="sm" @click="$inertia.visit('/teacher/classes')">
              <ArrowLeft class="h-4 w-4 mr-1" />
              Back to Classes
            </Button>
            <h1 class="text-3xl font-bold tracking-tight">{{ classData.name }}</h1>
          </div>
          <p class="text-muted-foreground">
            {{ classData.course }} - Section {{ classData.section }} ({{ classData.year }})
          </p>
        </div>
        <div class="flex gap-2">
          <Button @click="startAttendance">
            <Clock class="h-4 w-4 mr-2" />
            Take Attendance
          </Button>
          <Button variant="outline" @click="showAddStudentDialog = true">
            <Plus class="h-4 w-4 mr-2" />
            Add Student
          </Button>
        </div>
      </div>

      <!-- Class Info Cards -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Students</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ students.length }}</div>
            <p class="text-xs text-muted-foreground">Enrolled students</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Schedule</CardTitle>
            <Clock class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-lg font-bold">{{ classData.schedule_time }}</div>
            <p class="text-xs text-muted-foreground">
              {{ classData.schedule_days ? classData.schedule_days.join(', ') : 'No schedule set' }}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Recent Sessions</CardTitle>
            <Clock class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ recentSessions.length }}</div>
            <p class="text-xs text-muted-foreground">Attendance sessions</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Students</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ students.filter(s => s.status === 'enrolled').length }}</div>
            <p class="text-xs text-muted-foreground">Currently enrolled</p>
          </CardContent>
        </Card>
      </div>

      <!-- Students Table -->
      <Card>
        <CardHeader>
          <CardTitle>Class Students</CardTitle>
          <CardDescription>
            Manage students enrolled in this class
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="students.length === 0" class="text-center py-8">
            <Users class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
            <h3 class="text-lg font-semibold mb-2">No students enrolled</h3>
            <p class="text-muted-foreground mb-4">Add students to get started with this class.</p>
            <Button @click="showAddStudentDialog = true">
              <Plus class="h-4 w-4 mr-2" />
              Add First Student
            </Button>
          </div>

          <Table v-else>
            <TableHeader>
              <TableRow>
                <TableHead>Student</TableHead>
                <TableHead>Student ID</TableHead>
                <TableHead>Contact</TableHead>
                <TableHead>Year/Course</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Enrolled</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="student in students" :key="student.id">
                <TableCell>
                  <div>
                    <div class="font-medium">{{ student.first_name }} {{ student.last_name }}</div>
                    <div class="text-sm text-muted-foreground">{{ student.email }}</div>
                  </div>
                </TableCell>
                <TableCell>
                  <code class="text-sm">{{ student.student_id }}</code>
                </TableCell>
                <TableCell>
                  <div class="space-y-1">
                    <div class="flex items-center gap-1 text-sm">
                      <Mail class="h-3 w-3" />
                      {{ student.email }}
                    </div>
                    <div v-if="student.phone" class="flex items-center gap-1 text-sm">
                      <Phone class="h-3 w-3" />
                      {{ student.phone }}
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  {{ student.year }} {{ student.course }}
                </TableCell>
                <TableCell>
                  <Badge :variant="student.status === 'enrolled' ? 'default' : 'secondary'">
                    {{ student.status }}
                  </Badge>
                </TableCell>
                <TableCell>
                  {{ new Date(student.enrolled_at).toLocaleDateString() }}
                </TableCell>
                <TableCell>
                  <Button size="sm" variant="destructive" @click="removeStudent(student.id)">
                    <UserX class="h-3 w-3" />
                  </Button>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

    </div>

    <!-- Add Student Dialog -->
    <AddStudentDialog
      :open="showAddStudentDialog"
      :class-id="classData.id"
      @close="showAddStudentDialog = false"
      @student-added="handleStudentAdded"
    />
  </AppLayout>
</template>