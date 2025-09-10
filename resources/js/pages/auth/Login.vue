<script setup lang="ts">
import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle, GraduationCap, Users, Shield, Mail } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const selectedRole = ref('');

const roles = [
    { value: 'student', label: 'Student', icon: GraduationCap, description: 'Access your classes and attendance' },
    { value: 'teacher', label: 'Teacher', icon: Users, description: 'Manage classes and track attendance' },
    { value: 'admin', label: 'Admin', icon: Shield, description: 'System administration access' }
];

const getRoleIcon = (roleValue: string) => {
    const role = roles.find(r => r.value === roleValue);
    return role ? role.icon : Mail;
};
</script>

<template>
    <AuthBase title="Welcome to Attendify" description="Smart Attendance Management System - Enter your credentials to access your account">
        <Head title="Log in - Attendify" />


        <div v-if="status" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                <span class="text-sm font-medium text-green-800">{{ status }}</span>
            </div>
        </div>

        <Form
            v-bind="AuthenticatedSessionController.store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <!-- Role Selector -->
                <div class="grid gap-3">
                    <Label for="role" class="text-sm font-medium">Select Your Role</Label>
                    <Select v-model="selectedRole" name="role" required>
                        <SelectTrigger class="w-full h-12">
                            <SelectValue placeholder="Choose your role to continue">
                                <div v-if="selectedRole" class="flex items-center space-x-3">
                                    <component :is="getRoleIcon(selectedRole)" class="w-4 h-4" />
                                    <span>{{ roles.find(r => r.value === selectedRole)?.label }}</span>
                                </div>
                            </SelectValue>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="role in roles" :key="role.value" :value="role.value">
                                <div class="flex items-start space-x-3 py-2">
                                    <component :is="role.icon" class="w-5 h-5 mt-0.5 text-muted-foreground" />
                                    <div>
                                        <div class="font-medium">{{ role.label }}</div>
                                        <div class="text-xs text-muted-foreground">{{ role.description }}</div>
                                    </div>
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.role" />
                </div>

                <!-- Email/Username Field -->
                <div class="grid gap-3">
                    <Label for="email" class="text-sm font-medium">Email Address</Label>
                    <div class="relative">
                        <Mail class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            :tabindex="2"
                            autocomplete="email"
                            placeholder="Enter your email address"
                            class="pl-10 h-12"
                        />
                    </div>
                    <InputError :message="errors.email" />
                </div>

                <!-- Password Field -->
                <div class="grid gap-3">
                    <div class="flex items-center justify-between">
                        <Label for="password" class="text-sm font-medium">Password</Label>
                        <TextLink v-if="canResetPassword" :href="request()" class="text-sm hover:underline" :tabindex="5">
                            Forgot password?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="3"
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        class="h-12"
                    />
                    <InputError :message="errors.password" />
                </div>

                <!-- Remember Me -->
                <div class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" :tabindex="4" />
                    <Label for="remember" class="text-sm font-medium cursor-pointer">
                        Keep me signed in for 30 days
                    </Label>
                </div>

                <!-- Login Button -->
                <Button 
                    type="submit" 
                    class="w-full h-12 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-medium shadow-lg hover:shadow-xl transition-all duration-200" 
                    :tabindex="4" 
                    :disabled="processing || !selectedRole"
                >
                    <LoaderCircle v-if="processing" class="w-4 h-4 animate-spin mr-2" />
                    <span v-if="!processing">Sign In to Attendify</span>
                    <span v-else>Signing you in...</span>
                </Button>
            </div>

            <!-- Sign Up Link -->
            <div class="text-center">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-background px-2 text-muted-foreground">New to Attendify?</span>
                    </div>
                </div>
                <div class="mt-4">
                    <TextLink 
                        :href="register()" 
                        :tabindex="6"
                        class="inline-flex items-center justify-center w-full h-12 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-md transition-colors duration-200"
                    >
                        Create your account
                    </TextLink>
                </div>
            </div>
        </Form>
    </AuthBase>
</template>