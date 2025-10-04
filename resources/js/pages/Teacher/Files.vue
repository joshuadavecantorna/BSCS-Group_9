<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

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
  { title: 'Files', href: '/teacher/files' }
];
</script>

<template>
  <Head title="Teacher Files" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">
      
      <!-- Header Section -->
      <div class="flex items-center justify-between">
        <div class="space-y-2">
          <h1 class="text-3xl font-bold tracking-tight">File Management</h1>
          <p class="text-muted-foreground">
            Upload and manage files for your classes
          </p>
        </div>
        <Button @click="$inertia.post('/teacher/files/upload')">
          <span class="mr-2">📤</span>
          Upload File
        </Button>
      </div>

      <!-- File Upload Options -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="$inertia.visit('/teacher/files/upload')">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-6xl">📄</div>
            <CardTitle>Upload Documents</CardTitle>
            <CardDescription>Upload PDFs, Word docs, presentations</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button class="w-full">Upload Documents</Button>
          </CardContent>
        </Card>

        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="$inertia.visit('/teacher/files/images')">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-6xl">🖼️</div>
            <CardTitle>Upload Images</CardTitle>
            <CardDescription>Upload images and visual materials</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button variant="outline" class="w-full">Upload Images</Button>
          </CardContent>
        </Card>

        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="$inertia.visit('/teacher/files/videos')">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-6xl">🎥</div>
            <CardTitle>Upload Videos</CardTitle>
            <CardDescription>Upload video lectures and materials</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button variant="outline" class="w-full">Upload Videos</Button>
          </CardContent>
        </Card>
      </div>

      <!-- Recent Files -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Files</CardTitle>
          <CardDescription>Files you've uploaded recently</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
              <div class="flex items-center gap-3">
                <div class="text-2xl">📄</div>
                <div>
                  <p class="font-medium">Programming Fundamentals - Lecture 1.pdf</p>
                  <p class="text-sm text-muted-foreground">Uploaded today at 10:30 AM • 2.3 MB</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <Badge variant="secondary">BSCS-A</Badge>
                <Button size="sm" variant="outline">Download</Button>
              </div>
            </div>

            <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
              <div class="flex items-center gap-3">
                <div class="text-2xl">📊</div>
                <div>
                  <p class="font-medium">Data Structures Assignment.docx</p>
                  <p class="text-sm text-muted-foreground">Uploaded yesterday at 3:45 PM • 1.8 MB</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <Badge variant="secondary">BSCS-B</Badge>
                <Button size="sm" variant="outline">Download</Button>
              </div>
            </div>

            <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
              <div class="flex items-center gap-3">
                <div class="text-2xl">🖼️</div>
                <div>
                  <p class="font-medium">Algorithm Flowchart.png</p>
                  <p class="text-sm text-muted-foreground">Uploaded 2 days ago • 856 KB</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <Badge variant="secondary">BSCS-A</Badge>
                <Button size="sm" variant="outline">Download</Button>
              </div>
            </div>

            <div class="text-center py-4">
              <Button variant="outline" @click="$inertia.visit('/teacher/files/all')">
                View All Files
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- File Statistics -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Files</CardTitle>
            <span class="text-xl">📁</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">127</div>
            <p class="text-xs text-muted-foreground mt-1">
              Across all classes
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Storage Used</CardTitle>
            <span class="text-xl">💾</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">2.4 GB</div>
            <p class="text-xs text-muted-foreground mt-1">
              Of 10 GB available
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Downloads</CardTitle>
            <span class="text-xl">⬇️</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">483</div>
            <p class="text-xs text-muted-foreground mt-1">
              This month
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Recent Uploads</CardTitle>
            <span class="text-xl">📤</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">8</div>
            <p class="text-xs text-muted-foreground mt-1">
              This week
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- File Types Breakdown -->
      <Card>
        <CardHeader>
          <CardTitle>File Types</CardTitle>
          <CardDescription>Breakdown of your uploaded files by type</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="text-center p-4 border rounded-lg">
              <div class="text-3xl mb-2">📄</div>
              <p class="font-semibold">Documents</p>
              <p class="text-2xl font-bold text-blue-600">67</p>
              <p class="text-xs text-muted-foreground">PDF, DOC, PPT</p>
            </div>
            
            <div class="text-center p-4 border rounded-lg">
              <div class="text-3xl mb-2">🖼️</div>
              <p class="font-semibold">Images</p>
              <p class="text-2xl font-bold text-green-600">34</p>
              <p class="text-xs text-muted-foreground">PNG, JPG, SVG</p>
            </div>
            
            <div class="text-center p-4 border rounded-lg">
              <div class="text-3xl mb-2">🎥</div>
              <p class="font-semibold">Videos</p>
              <p class="text-2xl font-bold text-purple-600">12</p>
              <p class="text-xs text-muted-foreground">MP4, AVI, MOV</p>
            </div>
            
            <div class="text-center p-4 border rounded-lg">
              <div class="text-3xl mb-2">📊</div>
              <p class="font-semibold">Others</p>
              <p class="text-2xl font-bold text-orange-600">14</p>
              <p class="text-xs text-muted-foreground">ZIP, RAR, etc</p>
            </div>
          </div>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>