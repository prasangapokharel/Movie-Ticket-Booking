<div id="pageLoader" class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900 transition-opacity duration-300">
    <div class="relative flex flex-col items-center">
        <!-- Logo Container with Floating Animation -->
        <div class="animate-pulse-subtle mb-4">
            <!-- Replace with your actual logo -->
            <img class="h-16 w-auto drop-shadow-[0_0_15px_rgba(239,68,68,0.5)]" src="https://logos-world.net/wp-content/uploads/2022/03/AMC-Theatres-Symbol.png">
        </div>
        
        <!-- Premium Loading Animation -->
        <div class="mt-6 relative">
            <div class="typing-dots text-red-500 text-2xl font-bold tracking-widest">
                <span class="dot">.</span>
                <span class="dot">.</span>
                <span class="dot">.</span>
            </div>
            
            <!-- Subtle glow effect -->
            <div class="absolute -inset-4 bg-red-500/10 blur-xl rounded-full"></div>
        </div>
    </div>
</div>

<style>
/* Premium subtle pulse animation for logo */
@keyframes pulse-subtle {
    0%, 100% {
        transform: scale(1);
        filter: brightness(1);
    }
    50% {
        transform: scale(1.05);
        filter: brightness(1.2);
    }
}

.animate-pulse-subtle {
    animation: pulse-subtle 2s ease-in-out infinite;
}

/* Fast typing dots animation */
@keyframes typingDot {
    0%, 100% {
        opacity: 0.3;
        transform: translateY(0);
    }
    50% {
        opacity: 1;
        transform: translateY(-2px);
    }
}

.typing-dots .dot {
    display: inline-block;
    animation: typingDot 0.5s infinite;
}

.typing-dots .dot:nth-child(1) {
    animation-delay: 0s;
}

.typing-dots .dot:nth-child(2) {
    animation-delay: 0.1s;
}

.typing-dots .dot:nth-child(3) {
    animation-delay: 0.2s;
}

#pageLoader {
    opacity: 1;
    visibility: visible;
}

#pageLoader.hidden {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;


}

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.5);
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, #ef4444, #b91c1c);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(90deg, #ef4444, #b91c1c);
        }
        
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    
    // Hide loader when page is fully loaded
    window.addEventListener('load', function() {
        loader.classList.add('hidden');
    });
    
    // Fallback to hide loader after 3 seconds if load event doesn't fire
    setTimeout(() => {
        loader.classList.add('hidden');
    }, 3000);
});

// Function to show loader programmatically when needed
function showLoader() {
    const loader = document.getElementById('pageLoader');
    loader.classList.remove('hidden');
}

// Function to hide loader programmatically when needed
function hideLoader() {
    const loader = document.getElementById('pageLoader');
    loader.classList.add('hidden');
}
</script>

