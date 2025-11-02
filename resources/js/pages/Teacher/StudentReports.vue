<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

interface Props {
  teacher: {
    id: number;
    first_name: string;
    last_name: string;
    department: string;
    position: string;
    email: string;
    name?: string;
  };
  students: Array<{
    id: number;
    student_id: string;
    name: string;
    email: string;
    phone?: string;
    course: string;
    year: string;
    section: string;
    class_id: number;
    class_name: string;
    enrolled_at: string;
    attendance_rate: number;
    recent_attendance_rate: number;
    total_sessions: number;
    present_count: number;
    absent_count: number;
    late_count: number;
    excused_count: number;
    recent_pattern: string[];
    trend: 'improving' | 'declining' | 'stable';
    performance: 'excellent' | 'good' | 'fair' | 'poor';
    needs_attention: boolean;
  }>;
  classes: Array<{
    id: number;
    name: string;
    course: string;
    section: string;
  }>;
  summaryStats: {
    total_students: number;
    avg_attendance_rate: number;
    excellent_performers: number;
    good_performers: number;
    fair_performers: number;
    poor_performers: number;
    students_needing_attention: number;
    improving_trend: number;
    declining_trend: number;
  };
  filterOptions: {
    courses: string[];
    years: string[];
    sections: string[];
  };
  filters: {
    class_id?: number;
    course?: string;
    year?: string;
    section?: string;
    attendance_threshold?: number;
    search?: string;
  };

}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
  { title: 'Reports', href: '/teacher/reports' },
  { title: 'Student Reports', href: '/teacher/reports/students' }
];

const getAttendanceBadge = (rate: number) => {
  if (rate >= 90) return 'default';
  if (rate >= 75) return 'secondary';
  if (rate >= 60) return 'outline';
  return 'destructive';
};

const getAttendanceLabel = (rate: number) => {
  if (rate >= 90) return 'Excellent';
  if (rate >= 75) return 'Good';
  if (rate >= 60) return 'Fair';
  return 'Poor';
};
</script>

<template>
  <Head title="Student Reports" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">
      
      <!-- Header Section -->
      <div class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight">Student Reports</h1>
        <p class="text-muted-foreground">
          Individual student performance and attendance analytics
        </p>
      </div>

      <!-- Summary Stats -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Students</CardTitle>
            <span class="text-2xl">👥</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ students.length }}</div>
            <p class="text-xs text-muted-foreground">Across all classes</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Average Attendance</CardTitle>
            <span class="text-2xl">📊</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ students.length > 0 ? Math.round(students.reduce((sum, s) => sum + s.attendance_rate, 0) / students.length) : 0 }}%
            </div>
            <p class="text-xs text-muted-foreground">Overall average</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Excellent Attendance</CardTitle>
            <span class="text-2xl">⭐</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ students.filter(s => s.attendance_rate >= 90).length }}
            </div>
            <p class="text-xs text-muted-foreground">90% or higher</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Needs Attention</CardTitle>
            <span class="text-2xl">⚠️</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ students.filter(s => s.attendance_rate < 60).length }}
            </div>
            <p class="text-xs text-muted-foreground">Below 60%</p>
          </CardContent>
        </Card>
      </div>

      <!-- Students Table -->
      <Card>
        <CardHeader>
          <CardTitle>Student Performance</CardTitle>
          <CardDescription>
            Individual attendance records for all students
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="students.length === 0" class="text-center py-8">
            <p class="text-muted-foreground">No student data available.</p>
            <p class="text-sm text-muted-foreground mt-2">
              Students will appear here once they are enrolled in classes and attendance sessions are conducted.
            </p>
          </div>
          
          <Table v-else>
            <TableHeader>
              <TableRow>
                <TableHead>Student</TableHead>
                <TableHead>Student ID</TableHead>
                <TableHead>Class</TableHead>
                <TableHead>Sessions</TableHead>
                <TableHead>Present</TableHead>
                <TableHead>Attendance Rate</TableHead>
                <TableHead>Performance</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="student in students" :key="student.id">
                <TableCell>
                  <div>
                    <div class="font-medium">{{ student.name }}</div>
                    <div class="text-sm text-muted-foreground">{{ student.email }}</div>
                  </div>
                </TableCell>
                <TableCell>
                  <code class="text-sm">{{ student.student_id }}</code>
                </TableCell>
                <TableCell>{{ student.class_name }}</TableCell>
                <TableCell>{{ student.total_sessions }}</TableCell>
                <TableCell>
                  <span class="text-green-600 font-medium">{{ student.present_count }}</span>
                </TableCell>
                <TableCell>
                  <span class="font-medium">{{ student.attendance_rate }}%</span>
                </TableCell>
                <TableCell>
                  <Badge :variant="getAttendanceBadge(student.attendance_rate)">
                    {{ getAttendanceLabel(student.attendance_rate) }}
                  </Badge>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>