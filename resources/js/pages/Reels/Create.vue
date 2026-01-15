<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Video, Image as ImageIcon, Type, ArrowLeft, Upload, CheckCircle2 } from 'lucide-vue-next';

const form = useForm({
    type: 'video' as 'video' | 'image' | 'text',
    caption: '',
    content: '',
    file: null as File | null,
});

const filePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const triggerFileInput = () => {
    fileInput.value?.click();
};

const onFileChange = (e: any) => {
    const file = e.target.files[0];
    if (file) {
        form.file = file;
        filePreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post('/reels', {
        onSuccess: () => {
            form.reset();
            filePreview.value = null;
        },
    });
};

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reels', href: '/reels' },
    { title: 'Add', href: '/add' },
];
</script>

<template>
    <Head title="Create Reel" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-2xl p-4">
            <div class="flex items-center gap-4 mb-6">
                <Link href="/reels">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <h1 class="text-2xl font-bold">Create New Reel</h1>
            </div>

            <Card class="border-none shadow-lg">
                <CardHeader>
                    <CardTitle>Share something fun!</CardTitle>
                    <CardDescription>Upload a short video, image or post a funny text.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-8">
                        <!-- Type Selection -->
                        <div class="space-y-4">
                            <Label>What do you want to share?</Label>
                            <RadioGroup v-model="form.type" class="grid grid-cols-3 gap-4">
                                <Label
                                    for="type-video"
                                    class="flex flex-col items-center justify-between rounded-md border-2 border-muted bg-popover p-4 hover:bg-accent hover:text-accent-foreground peer-data-[state=checked]:border-primary [&:has([data-state=checked])]:border-primary cursor-pointer"
                                >
                                    <RadioGroupItem value="video" id="type-video" class="sr-only" />
                                    <Video class="mb-3 h-6 w-6" />
                                    <span class="text-xs font-semibold uppercase">Video</span>
                                </Label>
                                <Label
                                    for="type-image"
                                    class="flex flex-col items-center justify-between rounded-md border-2 border-muted bg-popover p-4 hover:bg-accent hover:text-accent-foreground peer-data-[state=checked]:border-primary [&:has([data-state=checked])]:border-primary cursor-pointer"
                                >
                                    <RadioGroupItem value="image" id="type-image" class="sr-only" />
                                    <ImageIcon class="mb-3 h-6 w-6" />
                                    <span class="text-xs font-semibold uppercase">Image</span>
                                </Label>
                                <Label
                                    for="type-text"
                                    class="flex flex-col items-center justify-between rounded-md border-2 border-muted bg-popover p-4 hover:bg-accent hover:text-accent-foreground peer-data-[state=checked]:border-primary [&:has([data-state=checked])]:border-primary cursor-pointer"
                                >
                                    <RadioGroupItem value="text" id="type-text" class="sr-only" />
                                    <Type class="mb-3 h-6 w-6" />
                                    <span class="text-xs font-semibold uppercase">Text</span>
                                </Label>
                            </RadioGroup>
                        </div>

                        <!-- Content Input -->
                        <div v-if="form.type === 'text'" class="space-y-2">
                            <Label for="content">Write your post</Label>
                            <Textarea 
                                id="content" 
                                v-model="form.content" 
                                placeholder="Write something interesting..." 
                                class="min-h-[150px] text-lg bg-slate-50 border-slate-200"
                            />
                            <p v-if="form.errors.content" class="text-xs text-red-500">{{ form.errors.content }}</p>
                        </div>

                        <!-- File Input -->
                        <div v-else class="space-y-4">
                            <Label>{{ form.type === 'video' ? 'Select Video (Max 1.5 min)' : 'Select Image' }}</Label>
                            
                            <div 
                                v-if="!filePreview"
                                class="border-2 border-dashed border-slate-200 rounded-xl p-12 text-center hover:bg-slate-50 transition-colors cursor-pointer relative"
                                @click="triggerFileInput"
                            >
                                <Upload class="h-10 w-10 text-slate-400 mx-auto mb-4" />
                                <p class="text-sm font-medium">Click to upload or drag and drop</p>
                                <p class="text-xs text-slate-400 mt-1">MP4, JPG, PNG or WEBP</p>
                                <input 
                                    type="file" 
                                    ref="fileInput" 
                                    class="hidden" 
                                    @change="onFileChange" 
                                    :accept="form.type === 'video' ? 'video/*' : 'image/*'" 
                                />
                            </div>

                            <div v-else class="relative rounded-xl overflow-hidden bg-black border border-slate-200 aspect-video flex items-center justify-center">
                                <video v-if="form.type === 'video'" :src="filePreview" class="max-h-full" controls></video>
                                <img v-else :src="filePreview" class="max-h-full object-contain" />
                                
                                <Button 
                                    type="button" 
                                    variant="destructive" 
                                    size="sm" 
                                    class="absolute top-2 right-2"
                                    @click="filePreview = null; form.file = null"
                                >
                                    Remove
                                </Button>
                            </div>
                            <p v-if="form.errors.file" class="text-xs text-red-500">{{ form.errors.file }}</p>
                        </div>

                        <!-- Caption -->
                        <div class="space-y-4 pt-4 border-t">
                            <Label for="caption">Caption (Optional)</Label>
                            <Input id="caption" v-model="form.caption" placeholder="Add a catchy caption..." />
                            <p v-if="form.errors.caption" class="text-xs text-red-500">{{ form.errors.caption }}</p>
                        </div>

                        <!-- Submit -->
                        <Button 
                            type="submit" 
                            class="w-full h-12 text-base font-bold bg-rose-600 hover:bg-rose-700" 
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Posting...</span>
                            <span v-else class="flex items-center gap-2">
                                <CheckCircle2 class="h-5 w-5" />
                                Post Reel
                            </span>
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
