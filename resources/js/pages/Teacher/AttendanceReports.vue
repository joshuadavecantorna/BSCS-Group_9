<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

interface Props {
  teacher: {
    id: number;
    first_name: string;
    last_name: string;
    department: string;
    position: string;
    email: string;
  };
  classes: Array<{
    id: number;
    name: string;
    course: string;
    section: string;
  }>;
  sessions: Array<{
    id: number;
    class_name: string;
    course: string;
    section: string;
    session_name: string;
    session_date: string;
    present_count: number;
    absent_count: number;
    total_count: number;
    status: string;
  }>;
  selectedClassId?: number;
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
  { title: 'Reports', href: '/teacher/reports' },
  { title: 'Attendance Reports', href: '/teacher/reports/attendance' }
];

const selectedClass = ref(props.selectedClassId?.toString() || 'all');

const filterByClass = () => {
  if (selectedClass.value && selectedClass.value !== 'all') {
    window.location.href = `/teacher/reports/attendance?class_id=${selectedClass.value}`;
  } else {
    window.location.href = '/teacher/reports/attendance';
  }
};

const getAttendancePercentage = (present: number, total: number) => {
  return total > 0 ? Math.round((present / total) * 100) : 0;
};

const getStatusBadge = (percentage: number) => {
  if (percentage >= 90) return 'default';
  if (percentage >= 75) return 'secondary';
  if (percentage >= 60) return 'outline';
  return 'destructive';
};
</script>

<template>
  <Head title="Attendance Reports" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">
      
      <!-- Header Section -->
      <div class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight">Attendance Reports</h1>
        <p class="text-muted-foreground">
          Detailed attendance analytics and trends for your classes
        </p>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex gap-4 items-end">
            <div class="flex-1">
              <label class="text-sm font-medium">Class</label>
              <Select v-model="selectedClass">
                <SelectTrigger>
                  <SelectValue placeholder="All Classes" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Classes</SelectItem>
                  <SelectItem v-for="cls in classes" :key="cls.id" :value="cls.id.toString()">
                    {{ cls.name }} ({{ cls.section }})
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <Button @click="filterByClass">Apply Filter</Button>
          </div>
        </CardContent>
      </Card>

      <!-- Sessions Table -->
      <Card>
        <CardHeader>
          <CardTitle>Attendance Sessions</CardTitle>
          <CardDescription>
            {{ sessions.length }} session(s) found
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="sessions.length === 0" class="text-center py-8">
            <p class="text-muted-foreground">No attendance sessions found.</p>
            <Button @click="$inertia.visit('/teacher/attendance')" class="mt-4">
              Start Taking Attendance
            </Button>
          </div>
          
          <Table v-else>
            <TableHeader>
              <TableRow>
                <TableHead>Class</TableHead>
                <TableHead>Session</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Present</TableHead>
                <TableHead>Absent</TableHead>
                <TableHead>Total</TableHead>
                <TableHead>Attendance Rate</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="session in sessions.filter(s => s && s.id)" :key="session.id">
                <TableCell>
                  <div>
                    <div class="font-medium">{{ session.class_name || 'Unknown Class' }}</div>
                    <div class="text-sm text-muted-foreground">{{ session.course || 'Unknown' }} - {{ session.section || 'Unknown' }}</div>
                  </div>
                </TableCell>
                <TableCell>{{ session.session_name || 'Unnamed Session' }}</TableCell>
                <TableCell>{{ session.session_date ? new Date(session.session_date).toLocaleDateString() : 'Unknown Date' }}</TableCell>
                <TableCell>
                  <span class="text-green-600 font-medium">{{ session.present_count || 0 }}</span>
                </TableCell>
                <TableCell>
                  <span class="text-red-600 font-medium">{{ session.absent_count || 0 }}</span>
                </TableCell>
                <TableCell>{{ session.total_count || 0 }}</TableCell>
                <TableCell>
                  <div class="flex items-center gap-2">
                    <span class="font-medium">{{ getAttendancePercentage(session.present_count || 0, session.total_count || 0) }}%</span>
                    <Badge :variant="getStatusBadge(getAttendancePercentage(session.present_count || 0, session.total_count || 0))">
                      {{ getAttendancePercentage(session.present_count || 0, session.total_count || 0) >= 75 ? 'Good' : 'Needs Attention' }}
                    </Badge>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge :variant="session.status === 'active' ? 'default' : 'secondary'">
                    {{ session.status || 'unknown' }}
                  </Badge> 
                </TableCell>
              </TableRow>
              <TableRow v-if="!sessions || sessions.length === 0">
                <TableCell colspan="8" class="text-center text-muted-foreground py-8">
                  No attendance sessions found
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>