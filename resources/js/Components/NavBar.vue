<script setup>
import { useGeneralStore } from '@/store/generalStore';

const store = useGeneralStore()

defineProps({
    text: String
})

const emits = defineEmits(['updateText'])

function handleInput(e) {
    
    if (e.target.value == '') {
        store.setGlobalText("Luminoforo")
    }else {
        store.setGlobalText(e.target.value)
    }
}

</script>

<template>
    <nav class="flex items-center justify-between bg-black px-4 sm:px-8 py-5 text-white">
        <img src="Luminoforo.png" alt="logo" class="hidden sm:block max-w-60">
        <img src="favicon.png" alt="logo" class="sm:hidden block max-w-10">
        <div id="text" class="flex w-1/3 sm:w-1/4">
            <input class=" rounded-l-full w-full border-none text-black" type="text" @change="handleInput" :value="text" placeholder="Type your word or phrase here">
            <button class=" bg-white text-slate-500 rounded-r-full border-none w-12 "><img src="angle.png" alt=""></button>
        </div>
        <div class="flex text-sm sm:text-base gap-1 sm:gap-3">
            <a href="">Help</a>
            |
            <a href="">Contact</a>
        </div>
    </nav>
</template>