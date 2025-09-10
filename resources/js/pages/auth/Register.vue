<script setup lang="ts">
import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle, GraduationCap, Users, Mail, User, Lock, Building, Hash } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const selectedRole = ref('');

const roles = [
    { value: 'student', label: 'Student', icon: GraduationCap, description: 'Join classes and track your attendance' },
    { value: 'teacher', label: 'Teacher', icon: Users, description: 'Create and manage classes' }
];

const showClassCode = computed(() => selectedRole.value === 'student');
const showSchoolName = computed(() => selectedRole.value === 'teacher');

const getRoleIcon = (roleValue: string) => {
    const role = roles.find(r => r.value === roleValue);
    return role ? role.icon : User;
};
</script>

<template>
    <AuthBase title="Join Attendify" description="Create your account to get started with smart attendance management">
        <Head title="Register - Attendify" />

        <Form
            v-bind="RegisteredUserController.store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <!-- Role Selection -->
                <div class="grid gap-3">
                    <Label for="role" class="text-sm font-medium">I am a</Label>
                    <Select v-model="selectedRole" name="role" required>
                        <SelectTrigger class="w-full h-12">
                            <SelectValue placeholder="Select your role">
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

                <!-- Full Name -->
                <div class="grid gap-3">
                    <Label for="name" class="text-sm font-medium">Full Name</Label>
                    <div class="relative">
                        <User class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input 
                            id="name" 
                            type="text" 
                            required 
                            autofocus 
                            :tabindex="2" 
                            autocomplete="name" 
                            name="name" 
                            placeholder="Enter your full name"
                            class="pl-10 h-12"
                        />
                    </div>
                    <InputError :message="errors.name" />
                </div>

                <!-- Email -->
                <div class="grid gap-3">
                    <Label for="email" class="text-sm font-medium">Email Address</Label>
                    <div class="relative">
                        <Mail class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input 
                            id="email" 
                            type="email" 
                            required 
                            :tabindex="3" 
                            autocomplete="email" 
                            name="email" 
                            placeholder="Enter your email address"
                            class="pl-10 h-12"
                        />
                    </div>
                    <InputError :message="errors.email" />
                </div>

                <!-- Conditional Fields -->
                <!-- Class Code for Students -->
                <div v-if="showClassCode" class="grid gap-3">
                    <Label for="class_code" class="text-sm font-medium">
                        Class Code 
                        <span class="text-xs text-muted-foreground font-normal">(Optional - you can join classes later)</span>
                    </Label>
                    <div class="relative">
                        <Hash class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input 
                            id="class_code" 
                            type="text" 
                            :tabindex="4"
                            name="class_code" 
                            placeholder="Enter class code (if you have one)"
                            class="pl-10 h-12"
                        />
                    </div>
                    <InputError :message="errors.class_code" />
                </div>

                <!-- School Name for Teachers -->
                <div v-if="showSchoolName" class="grid gap-3">
                    <Label for="school_name" class="text-sm font-medium">
                        School/Institution Name 
                        <span class="text-xs text-muted-foreground font-normal">(Optional)</span>
                    </Label>
                    <div class="relative">
                        <Building class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input 
                            id="school_name" 
                            type="text" 
                            :tabindex="4"
                            name="school_name" 
                            placeholder="Enter your school or institution name"
                            class="pl-10 h-12"
                        />
                    </div>
                    <InputError :message="errors.school_name" />
                </div>

                <!-- Password -->
                <div class="grid gap-3">
                    <Label for="password" class="text-sm font-medium">Password</Label>
                    <div class="relative">
                        <Lock class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input 
                            id="password" 
                            type="password" 
                            required 
                            :tabindex="5" 
                            autocomplete="new-password" 
                            name="password" 
                            placeholder="Create a strong password"
                            class="pl-10 h-12"
                        />
                    </div>
                    <div class="text-xs text-muted-foreground">
                        Password should be at least 8 characters long
                    </div>
                    <InputError :message="errors.password" />
                </div>

                <!-- Confirm Password -->
                <div class="grid gap-3">
                    <Label for="password_confirmation" class="text-sm font-medium">Confirm Password</Label>
                    <div class="relative">
                        <Lock class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            :tabindex="6"
                            autocomplete="new-password"
                            name="password_confirmation"
                            placeholder="Confirm your password"
                            class="pl-10 h-12"
                        />
                    </div>
                    <InputError :message="errors.password_confirmation" />
                </div>

                <!-- Create Account Button -->
                <Button 
                    type="submit" 
                    class="w-full h-12 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-medium shadow-lg hover:shadow-xl transition-all duration-200 mt-2" 
                    tabindex="7" 
                    :disabled="processing || !selectedRole"
                >
                    <LoaderCircle v-if="processing" class="w-4 h-4 animate-spin mr-2" />
                    <span v-if="!processing">Create My Account</span>
                    <span v-else>Creating your account...</span>
                </Button>

                <!-- Terms and Privacy Notice -->
                <div class="text-xs text-center text-muted-foreground">
                    By creating an account, you agree to our 
                    <TextLink href="#" class="underline hover:no-underline">Terms of Service</TextLink>
                    and 
                    <TextLink href="#" class="underline hover:no-underline">Privacy Policy</TextLink>
                </div>
            </div>

            <!-- Sign In Link -->
            <div class="text-center">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-background px-2 text-muted-foreground">Already have an account?</span>
                    </div>
                </div>
                <div class="mt-4">
                    <TextLink 
                        :href="login()" 
                        :tabindex="8"
                        class="inline-flex items-center justify-center w-full h-12 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-md transition-colors duration-200"
                    >
                        Sign in to your account
                    </TextLink>
                </div>
            </div>
        </Form>
    </AuthBase>
</template>