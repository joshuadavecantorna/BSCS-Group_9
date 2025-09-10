<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { QrCode, UserPlus, Users, UserCheck, UserX, Clock, TrendingUp } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

// Sample data - replace with your actual data from props/API
const attendanceStats = ref({
    totalStudents: 150,
    presentToday: 142,
    absentToday: 5,
    excusedToday: 3,
    dropped: 2
});

const lastScannedStudent = ref({
    name: 'Maria Santos',
    studentId: 'STU-2024-001',
    scanTime: '09:45 AM',
    status: 'present',
    avatar: null
});

const manualEntry = ref({
    studentId: '',
    status: 'present'
});

// Computed values for attendance percentage
const attendanceRate = computed(() => {
    const present = attendanceStats.value.presentToday + attendanceStats.value.excusedToday;
    const total = attendanceStats.value.totalStudents - attendanceStats.value.dropped;
    return Math.round((present / total) * 100);
});

// Methods
const openQRScanner = () => {
    // Implement QR scanner functionality
    console.log('Opening QR Scanner...');
};

const submitManualEntry = () => {
    if (manualEntry.value.studentId) {
        // Implement manual entry submission
        console.log('Manual entry:', manualEntry.value);
        manualEntry.value.studentId = '';
    }
};

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase();
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'present': return 'bg-green-500';
        case 'absent': return 'bg-red-500';
        case 'excused': return 'bg-yellow-500';
        default: return 'bg-gray-500';
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            
            <!-- Statistics Cards Row -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Students</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ attendanceStats.totalStudents }}</div>
                        <p class="text-xs text-muted-foreground">Active enrollees</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Present Today</CardTitle>
                        <UserCheck class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ attendanceStats.presentToday }}</div>
                        <p class="text-xs text-muted-foreground">{{ attendanceRate }}% attendance</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Absent Today</CardTitle>
                        <UserX class="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ attendanceStats.absentToday }}</div>
                        <p class="text-xs text-muted-foreground">Unexcused absences</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Excused Today</CardTitle>
                        <Clock class="h-4 w-4 text-yellow-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-yellow-600">{{ attendanceStats.excusedToday }}</div>
                        <p class="text-xs text-muted-foreground">With permission</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Dropped</CardTitle>
                        <TrendingUp class="h-4 w-4 text-gray-600 rotate-180" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-gray-600">{{ attendanceStats.dropped }}</div>
                        <p class="text-xs text-muted-foreground">No longer enrolled</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions Row -->
            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <QrCode class="h-5 w-5" />
                            QR Scanner
                        </CardTitle>
                        <CardDescription>Scan student QR codes for quick attendance</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button @click="openQRScanner" class="w-full" size="lg">
                            <QrCode class="mr-2 h-4 w-4" />
                            Open QR Scanner
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <UserPlus class="h-5 w-5" />
                            Manual Entry
                        </CardTitle>
                        <CardDescription>Manually mark student attendance</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="student-id">Student ID</Label>
                            <Input 
                                id="student-id" 
                                v-model="manualEntry.studentId"
                                placeholder="Enter student ID or name"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="status">Status</Label>
                            <Select v-model="manualEntry.status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="present">Present</SelectItem>
                                    <SelectItem value="absent">Absent</SelectItem>
                                    <SelectItem value="excused">Excused</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Button @click="submitManualEntry" class="w-full">
                            <UserPlus class="mr-2 h-4 w-4" />
                            Mark Attendance
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Activity -->
            <Card>
                <CardHeader>
                    <CardTitle>Last Scanned Student</CardTitle>
                    <CardDescription>Most recent QR scan activity</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center space-x-4">
                        <Avatar class="h-12 w-12">
                            <AvatarImage :src="lastScannedStudent.avatar || ''" :alt="lastScannedStudent.name" />
                            <AvatarFallback>{{ getInitials(lastScannedStudent.name) }}</AvatarFallback>
                        </Avatar>
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium leading-none">{{ lastScannedStudent.name }}</p>
                                <Badge :class="getStatusColor(lastScannedStudent.status)" class="text-white">
                                    {{ lastScannedStudent.status }}
                                </Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                ID: {{ lastScannedStudent.studentId }}
                            </p>
                        </div>
                        <div class="text-sm text-muted-foreground">
                            {{ lastScannedStudent.scanTime }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Analytics Section -->
            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Attendance Overview</CardTitle>
                        <CardDescription>Today's attendance distribution</CardDescription>
                    </CardHeader>
                    <CardContent class="flex items-center justify-center h-64">
                        <!-- Placeholder for Pie Chart -->
                        <div class="text-center text-muted-foreground">
                            <div class="w-32 h-32 rounded-full border-8 border-green-200 border-t-green-500 mx-auto mb-4"></div>
                            <p class="text-sm">Pie Chart Component</p>
                            <p class="text-xs">{{ attendanceRate }}% Present</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Attendance Trends</CardTitle>
                        <CardDescription>Weekly attendance patterns</CardDescription>
                    </CardHeader>
                    <CardContent class="flex items-center justify-center h-64">
                        <!-- Placeholder for Bar Chart -->
                        <div class="text-center text-muted-foreground">
                            <div class="flex items-end justify-center space-x-2 h-24 mb-4">
                                <div class="w-4 bg-blue-200 h-16"></div>
                                <div class="w-4 bg-blue-300 h-20"></div>
                                <div class="w-4 bg-blue-400 h-24"></div>
                                <div class="w-4 bg-blue-500 h-18"></div>
                                <div class="w-4 bg-blue-600 h-22"></div>
                            </div>
                            <p class="text-sm">Bar Chart Component</p>
                            <p class="text-xs">Weekly trends</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

        </div>
    </AppLayout>
</template>