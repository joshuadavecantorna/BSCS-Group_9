<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import QrScanner from '@/components/QrScanner.vue';

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' }
];

// QR Scanner state
const showQRScanner = ref(false);
const teacherName = ref("Prof. Juan Dela Cruz");

const openQRScanner = () => {
  showQRScanner.value = true;
};

const closeQRScanner = () => {
  showQRScanner.value = false;
};

const onScanSuccess = (studentData: any) => {
  console.log('Student scanned successfully:', studentData);
  alert(`Successfully scanned: ${studentData.name} (${studentData.student_id})`);
  closeQRScanner();
};
</script>

<template>
  <Head title="Dashboard" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <h1 class="text-3xl font-bold mb-4">Dashboard - QR Scanner Test</h1>
      <p class="text-green-600 mb-4">✓ Basic layout works with minimal QR Scanner</p>
      
      <!-- Simple stats -->
      <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="p-4 bg-white border rounded-lg shadow-sm">
          <h3 class="font-semibold">Total Students</h3>
          <p class="text-2xl">150</p>
        </div>
        <div class="p-4 bg-white border rounded-lg shadow-sm">
          <h3 class="font-semibold">Present Today</h3>
          <p class="text-2xl text-green-600">142</p>
        </div>
        <div class="p-4 bg-white border rounded-lg shadow-sm">
          <h3 class="font-semibold">QR Scanner Test</h3>
          <button 
            @click="openQRScanner" 
            class="mt-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
          >
            Open QR Scanner
          </button>
        </div>
      </div>
      
      <p>Teacher: {{ teacherName }}</p>
      <p>QR Scanner state: {{ showQRScanner }}</p>
    </div>

    <!-- QR Scanner Component -->
    <QrScanner 
      :show="showQRScanner"
      @close="closeQRScanner"
      @scan-success="onScanSuccess"
    />
  </AppLayout>
</template>