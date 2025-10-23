<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';

interface Props {
  teacher: {
    id: number;
    first_name: string;
    last_name: string;
    department: string;
    position: string;
    email: string;
  };
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
  { title: 'Reports', href: '/teacher/reports' }
];
</script>

<template>
  <Head title="Teacher Reports" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">
      
      <!-- Header Section -->
      <div class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight">Reports & Analytics</h1>
        <p class="text-muted-foreground">
          View detailed reports and analytics for your classes
        </p>
      </div>

      <!-- Report Types -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="$inertia.visit('/teacher/reports/attendance')">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-6xl">📊</div>
            <CardTitle>Attendance Reports</CardTitle>
            <CardDescription>Detailed attendance analytics and trends</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button class="w-full">View Attendance</Button>
          </CardContent>
        </Card>

        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="$inertia.visit('/teacher/reports/students')">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-6xl">👥</div>
            <CardTitle>Student Reports</CardTitle>
            <CardDescription>Individual student performance and attendance</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button variant="outline" class="w-full">Student Analytics</Button>
          </CardContent>
        </Card>

        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="$inertia.visit('/teacher/reports/export')">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-6xl">📥</div>
            <CardTitle>Export Data</CardTitle>
            <CardDescription>Download reports in various formats</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button variant="outline" class="w-full">Export Reports</Button>
          </CardContent>
        </Card>
      </div>

      <!-- Quick Reports Summary -->
      <div class="grid gap-6 md:grid-cols-2">
        <!-- Attendance Summary -->
        <Card>
          <CardHeader>
            <CardTitle>Weekly Attendance Summary</CardTitle>
            <CardDescription>Overview of this week's attendance</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div class="flex justify-between items-center">
                <span class="text-sm font-medium">Introduction to Programming</span>
                <div class="flex items-center gap-2">
                  <span class="text-sm text-muted-foreground">92%</span>
                  <Badge variant="default">Excellent</Badge>
                </div>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-green-600 h-2 rounded-full" style="width: 92%"></div>
              </div>
              
              <div class="flex justify-between items-center">
                <span class="text-sm font-medium">Data Structures & Algorithms</span>
                <div class="flex items-center gap-2">
                  <span class="text-sm text-muted-foreground">85%</span>
                  <Badge variant="secondary">Good</Badge>
                </div>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" style="width: 85%"></div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Recent Activities -->
        <Card>
          <CardHeader>
            <CardTitle>Report Activities</CardTitle>
            <CardDescription>Recent report generation and exports</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div class="flex items-center justify-between p-3 border rounded-lg">
                <div>
                  <p class="font-medium text-sm">Monthly Attendance Report</p>
                  <p class="text-xs text-muted-foreground">Generated today at 2:30 PM</p>
                </div>
                <Badge variant="outline">PDF</Badge>
              </div>
              
              <div class="flex items-center justify-between p-3 border rounded-lg">
                <div>
                  <p class="font-medium text-sm">Student Performance Analysis</p>
                  <p class="text-xs text-muted-foreground">Generated yesterday</p>
                </div>
                <Badge variant="outline">Excel</Badge>
              </div>
              
              <div class="flex items-center justify-between p-3 border rounded-lg">
                <div>
                  <p class="font-medium text-sm">Weekly Summary Report</p>
                  <p class="text-xs text-muted-foreground">Generated 2 days ago</p>
                </div>
                <Badge variant="outline">PDF</Badge>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Statistics Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Reports</CardTitle>
            <span class="text-xl">📋</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">47</div>
            <p class="text-xs text-muted-foreground mt-1">
              Generated this month
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Average Attendance</CardTitle>
            <span class="text-xl">📈</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">88.5%</div>
            <p class="text-xs text-muted-foreground mt-1">
              <span class="text-green-600">+2.1%</span> from last month
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Students</CardTitle>
            <span class="text-xl">👨‍🎓</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">73</div>
            <p class="text-xs text-muted-foreground mt-1">
              Across all classes
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Reports Downloaded</CardTitle>
            <span class="text-xl">⬇️</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">23</div>
            <p class="text-xs text-muted-foreground mt-1">
              This week
            </p>
          </CardContent>
        </Card>
      </div>

    </div>
  </AppLayout>
</template>