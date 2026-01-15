<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Card } from '@/components/ui/card';
import { Heart, MessageCircle, Share2, MoreVertical, Trash2, User, Plus, SquarePlay, Play, Volume2, VolumeX } from 'lucide-vue-next';

interface Reel {
    id: number;
    user_id: number;
    type: 'video' | 'image' | 'text';
    file_path: string | null;
    content: string | null;
    caption: string | null;
    user: {
        id: number;
        name: string;
    };
    created_at: string;
}

interface PaginatedReels {
    data: Reel[];
    links: any[];
    next_page_url: string | null;
}

const props = defineProps<{
    reels: PaginatedReels;
}>();

const videoRefs = ref<Record<number, HTMLVideoElement>>({});
const pausedStates = ref<Record<number, boolean>>({});
const isMuted = ref(true);

const toggleMute = () => {
    isMuted.value = !isMuted.value;
    Object.values(videoRefs.value).forEach(video => {
        video.muted = isMuted.value;
    });
};

const setVideoRef = (el: any, id: number) => {
    if (el) {
        videoRefs.value[id] = el;
        // Initialize state
        if (pausedStates.value[id] === undefined) {
            pausedStates.value[id] = true;
        }
    }
};

// Intersection Observer for auto-playing videos
let observer: IntersectionObserver | null = null;

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const video = entry.target as HTMLVideoElement;
            const id = parseInt(video.dataset.id || '0');
            if (entry.isIntersecting) {
                video.muted = isMuted.value;
                video.play().then(() => {
                    pausedStates.value[id] = false;
                }).catch(error => {
                    console.error("Video play failed:", error);
                    pausedStates.value[id] = true;
                });
            } else {
                video.pause();
                pausedStates.value[id] = true;
            }
        });
    }, { threshold: 0.5 });

    Object.values(videoRefs.value).forEach(v => observer?.observe(v));
});

onUnmounted(() => {
    observer?.disconnect();
});

const deleteReel = (id: number) => {
    if (confirm('Delete this reel?')) {
        router.delete(`/reels/${id}`, {
            preserveScroll: true,
        });
    }
};

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reels', href: '/reels' },
];

const getInitials = (name: string) => name.split(' ').map(n => n[0]).join('').toUpperCase();

const togglePlay = (id: number) => {
    const video = videoRefs.value[id];
    if (video) {
        if (video.paused) {
            video.play();
            // If user manually plays, we can try to unmute if they expect sound
            if (isMuted.value) {
                // Some browsers might still block this if not a direct user click on a "volume" button
                // but usually fine here.
            }
        } else {
            video.pause();
        }
    }
};
</script>

<template>
    <Head title="Explore Reels" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col items-center justify-center bg-white min-h-screen pb-20 pt-4">
            <!-- Feed -->
            <div class="space-y-6 w-full max-w-[450px]">
                <div v-if="reels.data.length === 0" class="text-center text-slate-500 py-20">
                    <p>No reels yet. Be the first to post something!</p>
                    <Link href="/add" class="mt-4 block">
                        <Button variant="outline">Create Reel</Button>
                    </Link>
                </div>

                <div v-for="reel in reels.data" :key="reel.id" class="relative snap-start flex justify-center h-[70vh] md:h-[80vh]">
                    <Card class="w-full h-full overflow-hidden rounded-xl border border-slate-200 bg-black flex flex-col relative group">
                        
                        <!-- Video Content -->
                        <div v-if="reel.type === 'video'" class="flex-1 bg-black relative flex items-center justify-center cursor-pointer overflow-hidden" @click="togglePlay(reel.id)">
                            <video 
                                class="w-full h-full object-cover" 
                                loop 
                                :muted="isMuted"
                                autoplay
                                preload="auto"
                                playsinline
                                :ref="el => setVideoRef(el, reel.id)"
                                :data-id="reel.id"
                            >
                                <source :src="reel.file_path || undefined" type="video/mp4">
                            </video>
                            
                            <!-- Volume Toggle Button -->
                            <Button 
                                variant="ghost" 
                                size="icon" 
                                @click.stop="toggleMute"
                                class="absolute top-4 right-4 z-20 h-10 w-10 rounded-full bg-black/20 backdrop-blur-md text-white hover:bg-black/40"
                            >
                                <Volume2 v-if="!isMuted" class="h-5 w-5" />
                                <VolumeX v-else class="h-5 w-5" />
                            </Button>

                            <!-- Play Icon Overlay -->
                            <div v-if="pausedStates[reel.id]" class="absolute inset-0 flex items-center justify-center bg-black/10 pointer-events-none">
                                <div class="bg-white/20 backdrop-blur-md rounded-full p-6 ring-1 ring-white/30">
                                    <Play class="h-12 w-12 text-white fill-white" />
                                </div>
                            </div>
                        </div>

                        <!-- Image Content -->
                        <div v-else-if="reel.type === 'image'" class="flex-1 bg-black relative flex items-center justify-center overflow-hidden">
                            <img :src="reel.file_path || undefined" class="w-full h-full object-cover" />
                        </div>

                        <!-- Text Content -->
                        <div v-else class="flex-1 bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 p-8 flex items-center justify-center text-center">
                            <p class="text-2xl font-bold text-white leading-relaxed">{{ reel.content }}</p>
                        </div>

                        <!-- Overlay Info (Bottom) -->
                        <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black/90 via-black/40 to-transparent text-white pointer-events-none">
                            <div class="flex items-center gap-3 mb-2">
                                <Avatar class="h-9 w-9 border-2 border-white/20">
                                    <AvatarFallback class="bg-slate-700 text-xs">{{ getInitials(reel.user.name) }}</AvatarFallback>
                                </Avatar>
                                <span class="font-bold text-sm drop-shadow-md">{{ reel.user.name }}</span>
                            </div>
                            <p v-if="reel.caption" class="text-sm opacity-95 line-clamp-2 drop-shadow-md leading-relaxed">{{ reel.caption }}</p>
                        </div>

                        <!-- Interaction Bar (Right Side) -->
                        <div class="absolute right-4 bottom-24 flex flex-col gap-5 items-center z-10">
                            <div class="flex flex-col items-center gap-1">
                                <Button variant="ghost" size="icon" class="h-12 w-12 rounded-full bg-white/10 backdrop-blur-lg text-white hover:bg-white/20 transition-all active:scale-95">
                                    <Heart class="h-7 w-7" />
                                </Button>
                                <span class="text-[11px] text-white font-bold drop-shadow-md uppercase tracking-tighter">Like</span>
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <Button variant="ghost" size="icon" class="h-12 w-12 rounded-full bg-white/10 backdrop-blur-lg text-white hover:bg-white/20 transition-all active:scale-95">
                                    <MessageCircle class="h-7 w-7" />
                                </Button>
                                <span class="text-[11px] text-white font-bold drop-shadow-md uppercase tracking-tighter">Chat</span>
                            </div>
                            
                            <!-- Delete action for owner/admin -->
                            <div v-if="reel.user_id === $page.props.auth.user.id" class="flex flex-col items-center gap-1">
                                <Button @click.stop="deleteReel(reel.id)" variant="ghost" size="icon" class="h-12 w-12 rounded-full bg-red-500/20 backdrop-blur-lg text-red-100 hover:bg-red-500 hover:text-white transition-all">
                                    <Trash2 class="h-6 w-6" />
                                </Button>
                                <span class="text-[11px] text-white font-bold drop-shadow-md uppercase tracking-tighter">Del</span>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Load More (Pagination) -->
            <div v-if="reels.next_page_url" class="mt-8 pb-10">
                <Link :href="reels.next_page_url" preserve-scroll>
                    <Button variant="outline">Load More</Button>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.snap-start {
    scroll-snap-align: start;
}
</style>
