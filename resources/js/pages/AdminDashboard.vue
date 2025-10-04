<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Users, GraduationCap, BookOpen, Clock, TrendingUp, AlertTriangle, CheckCircle } from 'lucide-vue-next';

interface Props {
  stats: {
    total_students: number;
    total_teachers: number;
    total_classes: number;
    active_sessions: number;
  };
  attendanceStats: {
    overall_present_percentage: number;
    total_absences: number;
    total_excuses: number;
  };
  recentActivity: Array<{
    id: number;
    type: string;
    message: string;
    timestamp: string;
    user: string;
  }>;
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Admin Dashboard', href: '/dashboard' }
];

// Helper functions for displaying activity types
const getActivityIcon = (type: string) => {
  switch(type) {
    case 'teacher_created': return CheckCircle;
    case 'class_created': return BookOpen;
    case 'system_update': return TrendingUp;
    default: return AlertTriangle;
  }
};

const getActivityVariant = (type: string) => {
  switch(type) {
    case 'teacher_created': return 'default';
    case 'class_created': return 'secondary';
    case 'system_update': return 'outline';
    default: return 'destructive';
  }
};

const formatTimestamp = (timestamp: string) => {
  return new Date(timestamp).toLocaleString();
};
</script>

<template>
  <Head title="Admin Dashboard" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-4 p-4 pt-4">
      <div class="space-y-6">
      
      <!-- Header Section -->
      <div class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight">Admin Dashboard</h1>
        <p class="text-muted-foreground">
          System overview and management console
        </p>
      </div>

      <!-- System Overview Stats -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Students</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.stats.total_students }}</div>
            <p class="text-xs text-muted-foreground">
              Enrolled students system-wide
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Teachers</CardTitle>
            <GraduationCap class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.stats.total_teachers }}</div>
            <p class="text-xs text-muted-foreground">
              Active faculty members
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Classes Created</CardTitle>
            <BookOpen class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.stats.total_classes }}</div>
            <p class="text-xs text-muted-foreground">
              Total classes in system
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Sessions</CardTitle>
            <Clock class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.stats.active_sessions }}</div>
            <p class="text-xs text-muted-foreground">
              Currently running attendance
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Global Attendance Stats -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Overall Present %</CardTitle>
            <TrendingUp class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ props.attendanceStats.overall_present_percentage }}%</div>
            <p class="text-xs text-muted-foreground">
              School-wide attendance rate
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Absences</CardTitle>
            <AlertTriangle class="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ props.attendanceStats.total_absences }}</div>
            <p class="text-xs text-muted-foreground">
              Students marked absent
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Excuses</CardTitle>
            <CheckCircle class="h-4 w-4 text-yellow-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-yellow-600">{{ props.attendanceStats.total_excuses }}</div>
            <p class="text-xs text-muted-foreground">
              Excused absences
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Quick Actions -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="router.visit('/admin/teachers')">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-4xl">👥</div>
            <CardTitle>Teachers</CardTitle>
            <CardDescription>Manage teacher accounts</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button class="w-full">Manage Teachers</Button>
          </CardContent>
        </Card>

        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="router.visit('/admin/reports')">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-4xl">📊</div>
            <CardTitle>Reports</CardTitle>
            <CardDescription>School-wide reports</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button variant="outline" class="w-full">View Reports</Button>
          </CardContent>
        </Card>

        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="router.visit('/admin/settings')">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-4xl">⚙️</div>
            <CardTitle>Settings</CardTitle>
            <CardDescription>System configuration</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button variant="outline" class="w-full">System Settings</Button>
          </CardContent>
        </Card>

        <Card class="hover:shadow-md transition-shadow cursor-pointer">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-4xl">📋</div>
            <CardTitle>Announcements</CardTitle>
            <CardDescription>System announcements</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button variant="outline" class="w-full">View All</Button>
          </CardContent>
        </Card>
      </div>

      <!-- Activity Logs -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Activity Logs</CardTitle>
          <CardDescription>Latest system activities from teachers and administrators</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div class="space-y-4">
                <template v-for="(activity, index) in props.recentActivity" :key="activity.id">
                  <div class="flex items-center justify-between p-4 border rounded-lg">
                    <div class="flex items-center space-x-3">
                      <component :is="getActivityIcon(activity.type)" class="h-5 w-5 text-muted-foreground" />
                      <div>
                        <p class="text-sm font-medium">{{ activity.message }}</p>
                        <p class="text-xs text-muted-foreground">{{ formatTimestamp(activity.timestamp) }} • {{ activity.user }}</p>
                      </div>
                    </div>
                    <Badge :variant="getActivityVariant(activity.type)" class="text-xs">
                      {{ activity.type.replace('_', ' ').toUpperCase() }}
                    </Badge>
                  </div>
                </template>
            </div>
          </div>
        </CardContent>
      </Card>

      </div>
    </div>
  </AppLayout>
</template>