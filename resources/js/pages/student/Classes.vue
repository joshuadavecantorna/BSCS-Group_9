![1759654648089](image/Classes/1759654648089.png)![1759654650236](image/Classes/1759654650236.png)![1759654652845](image/Classes/1759654652845.png)![1759654662174](image/Classes/1759654662174.png)![1759654672935](image/Classes/1759654672935.png)![1759654673259](image/Classes/1759654673259.png)![1759654687683](image/Classes/1759654687683.png)<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Clock, MapPin, User, Calendar } from 'lucide-vue-next';
import { ref, computed } from 'vue';

// Props from StudentController
interface Props {
  classes?: any[];
  student?: any;
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/student/dashboard' },
  { title: 'Classes', href: '#' }
];

// Use backend data with computed property
const enrolledClasses = computed(() => {
  return props.classes || [];
});

const getAttendanceColor = (rate: number) => {
  if (rate >= 90) return 'text-green-600';
  if (rate >= 75) return 'text-yellow-600';
  return 'text-red-600';
};

const getAttendanceBadge = (rate: number) => {
  if (rate >= 90) return 'default';
  if (rate >= 75) return 'secondary';
  return 'destructive';
};
</script>

<template>
  <Head title="My Classes" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold">My Classes</h1>
        <p class="text-muted-foreground">View your enrolled classes and attendance summary</p>
      </div>

      <!-- Classes Grid -->
      <div v-if="enrolledClasses.length > 0" class="grid gap-6 md:grid-cols-2">
        <Card v-for="classItem in enrolledClasses" :key="classItem.id" class="hover:shadow-md transition-shadow">
          <CardHeader>
            <div class="flex items-start justify-between">
              <div>
                <CardTitle class="text-lg">{{ classItem.name }}</CardTitle>
                <CardDescription class="font-mono text-sm">{{ classItem.course_code || 'N/A' }}</CardDescription>
              </div>
              <Badge :variant="getAttendanceBadge(classItem.attendance_rate || 0)">
                {{ classItem.attendance_rate || 0 }}%
              </Badge>
            </div>
          </CardHeader>
          <CardContent class="space-y-4">
            <!-- Instructor -->
            <div class="flex items-center space-x-2 text-sm">
              <User class="h-4 w-4 text-muted-foreground" />
              <span>{{ classItem.teacher_name || 'No instructor assigned' }}</span>
            </div>

            <!-- Schedule -->
            <div class="flex items-center space-x-2 text-sm">
              <Clock class="h-4 w-4 text-muted-foreground" />
              <span>{{ classItem.schedule_time || 'No schedule set' }}</span>
            </div>

            <!-- Room -->
            <div class="flex items-center space-x-2 text-sm">
              <MapPin class="h-4 w-4 text-muted-foreground" />
              <span>Room {{ classItem.room || 'TBA' }}</span>
            </div>

            <!-- Attendance Summary -->
            <div class="pt-4 border-t">
              <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium">Attendance Rate</span>
                <span class="text-sm font-bold" :class="getAttendanceColor(classItem.attendance_rate || 0)">
                  {{ classItem.attendance_rate || 0 }}%
                </span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div 
                  class="h-2 rounded-full transition-all duration-300"
                  :class="(classItem.attendance_rate || 0) >= 90 ? 'bg-green-500' : (classItem.attendance_rate || 0) >= 75 ? 'bg-yellow-500' : 'bg-red-500'"
                  :style="`width: ${classItem.attendance_rate || 0}%`"
                ></div>
              </div>
              <div class="flex justify-between text-xs text-muted-foreground mt-1">
                <span>{{ classItem.attended_sessions || 0 }}/{{ classItem.total_sessions || 0 }} sessions</span>
                <span>{{ (classItem.total_sessions || 0) - (classItem.attended_sessions || 0) }} missed</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <Card>
          <CardContent class="py-12">
            <p class="text-muted-foreground text-lg">No classes enrolled yet</p>
            <p class="text-sm text-muted-foreground mt-2">Contact your administrator to enroll in classes</p>
          </CardContent>
        </Card>
      </div>

      <!-- Overall Summary -->
      <Card>
        <CardHeader>
          <CardTitle>Overall Summary</CardTitle>
          <CardDescription>Your attendance across all classes</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-4">
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-600">{{ enrolledClasses.length }}</div>
              <div class="text-sm text-muted-foreground">Total Classes</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600">
                {{ Math.round(enrolledClasses.reduce((sum, c) => sum + c.attendanceRate, 0) / enrolledClasses.length) }}%
              </div>
              <div class="text-sm text-muted-foreground">Average Attendance</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-purple-600">
                {{ enrolledClasses.reduce((sum, c) => sum + c.attendedSessions, 0) }}
              </div>
              <div class="text-sm text-muted-foreground">Sessions Attended</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-orange-600">
                {{ enrolledClasses.reduce((sum, c) => sum + (c.totalSessions - c.attendedSessions), 0) }}
              </div>
              <div class="text-sm text-muted-foreground">Sessions Missed</div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
