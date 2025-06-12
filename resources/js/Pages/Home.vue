<script setup>
import { ref, computed, onMounted, watchEffect, watch, nextTick, onUnmounted } from "vue";
import { useGeneralStore } from "@/store/generalStore";
import axios from "axios";

const store = useGeneralStore()
const fonts = ref([]);
const selectedCategory = ref(null);
const selectedSubCategory = ref(null);
const filteredFonts = ref([]);
const fontStyles = ref("");
const text = computed({
  get: () => store.globalText,
  set: (value) => {
    store.globalText = value;
  }
});
const caseMode = ref(0);
const selectedFonts = ref([])
const isItalic = ref()
const isAllSelected = ref(false)
const fontPreview = ref()

const dynamicFontSize = ref(20);
const fontElements = ref({});
const hiddenSpan = ref(null);
const fontSizePreview = ref(50);
let intervalId = null;

const card = ref(null);

const calculateFontSize = () => {
    if (card.value && hiddenSpan.value) {
        let cardWidth = card.value.clientWidth * 0.9; // 90% del ancho de la card
        let textWidth = hiddenSpan.value.offsetWidth;

        // Tamaño base de la fuente
        let fontSize = 50;

        // Si el texto es más grande que el 90% de la card, reducimos la fuente
        if (textWidth > cardWidth) {
            fontSize = Math.max(20, 50 * (cardWidth / textWidth)); // Nunca menos de 20px
        }

        dynamicFontSize.value = fontSize;
    }
};

const increaseFontSize = () => {
    fontSizePreview.value += 2; // Aumenta el tamaño en 2px
};

const decreaseFontSize = () => {
    if (fontSizePreview.value > 8) {
        fontSizePreview.value -= 2; // Disminuye el tamaño en 2px, sin bajar de 12px
    }
};

const startIncreasing = () => {
    increaseFontSize(); // Aumenta inmediatamente
    intervalId = setInterval(increaseFontSize, 100); // Aumenta cada 100ms
};

// Función para iniciar la disminución continua
const startDecreasing = () => {
    decreaseFontSize(); // Disminuye inmediatamente
    intervalId = setInterval(decreaseFontSize, 100); // Disminuye cada 100ms
};

// Detener la acción cuando se suelta el botón
const stopChanging = () => {
    clearInterval(intervalId);
};

// Ajustar tamaño de fuente individualmente para cada card
const resizeFont = (fontName) => {
    nextTick(() => {
        const fontElement = fontElements.value[fontName];;
        
        if (fontElement) {
            let parent = fontElement.parentElement;
            let maxHeight = parent.clientHeight;
            let maxWidth = parent.clientWidth;
            let fontSize = 40; // Tamaño inicial

            fontElement.style.fontSize = `${fontSize}px`; // Reset antes de ajustar

            while (
                fontElement.scrollHeight > maxHeight ||
                fontElement.scrollWidth > maxWidth
            ) {
                fontSize -= 1; // Ajuste gradual
                fontElement.style.fontSize = `${fontSize}px`;

                if (fontSize < 14) break; // Evita reducir demasiado
            }
        }
    });
};

const toggleCase = (index) => {
    calculateFontSize()
    
    caseMode.value = index;
};

const toggleItalic = (value) => {
    isItalic.value = value
};

const toggleSelect = () => {

    if (isAllSelected.value === true) {
        selectedFonts.value.length = 0
        isAllSelected.value = false
    }

};

const handlePreview = (value) => {
    fontPreview.value = value; 

    nextTick(() => {
        resizeFont(value.styleName); // Ejecutar solo cuando el DOM esté actualizado
    });
};

const handleClose = () => {
    toggleCase(3);
    toggleItalic(false)
    fontPreview.value = null;
};


const handleSelect = (font) => {

    const index = selectedFonts.value.indexOf(font);

    if (index !== -1) {
        selectedFonts.value.splice(index, 1);
        if (selectedFonts.value.length === 0) {
            isAllSelected.value = false
        }
    } else {
        selectedFonts.value.push(font);
        isAllSelected.value = true
    }

}

const particularToggleCase = (index, fontName) => {
    const element = fontElements.value[fontName];

    if (element) {
        if (index === 1) {
            element.style.textTransform = "uppercase";
        } else if (index === 2) {
            element.style.textTransform = "lowercase";
        } else if (index === 3) {
            element.style.textTransform = "capitalize";
        }
    }
};

const particularToggleItalic = (isItalic, fontName) => {

        const element = fontElements.value[fontName];
    
        if (element) {
            element.style.fontStyle = isItalic ? "italic" : "normal";
        }
};

const textTransform = computed(() => {
    return caseMode.value === 1 ? "uppercase"
        : caseMode.value === 2 ? "lowercase"
            : caseMode.value === 3 ? "capitalize"
                : "none";
});

const flattenFonts = (data) => {
    let allFonts = [];
    Object.entries(data).forEach(([category, subcategories]) => {
        Object.entries(subcategories).forEach(([subCategory, fonts]) => {
            allFonts.push(...fonts);
        });
    });
    return allFonts;
};

const filterFonts = () => {
    if (!selectedCategory.value) {
        filteredFonts.value = flattenFonts(fonts.value);
    } else if (!selectedSubCategory.value) {
        filteredFonts.value = flattenFonts({ [selectedCategory.value]: fonts.value[selectedCategory.value] });
    } else {
        filteredFonts.value = fonts.value[selectedCategory.value]?.[selectedSubCategory.value] || [];
    }
    updateFontStyles(filteredFonts.value);
};

const updateFontStyles = (fontList) => {
    fontStyles.value = fontList
        .map(
            (font) => `
          @font-face {
            font-family: '${font.styleName}';
            font-style: normal;
            font-weight: 200 700;
            font-display: swap;
            src: url('${font.url}') format('truetype');
          }
        `
        )
        .join("\n");

    let existingStyleTag = document.getElementById("dynamic-fonts");
    if (existingStyleTag) {
        existingStyleTag.remove();
    }

    // Crear un nuevo style tag
    const styleTag = document.createElement("style");
    styleTag.id = "dynamic-fonts";
    styleTag.innerHTML = fontStyles.value;
    document.head.appendChild(styleTag);
};

watchEffect(() => {
    text.value = store.globalText
    console.log(text.value);
    
});

watchEffect(() => {
    console.log(text.value);
  nextTick(() => resizeFont());
});

watch(text, () => {
    
  nextTick(() => {
    calculateFontSize();
  });
});

onMounted(async () => {
    try {
        const response = await axios.get("/api/fonts");
        
        fonts.value = response.data;
        filteredFonts.value = flattenFonts(response.data);
        updateFontStyles(filteredFonts.value);
        onMounted(resizeFont);
        window.addEventListener("resize", calculateFontSize);
    } catch (error) {
        console.error("Error al obtener fuentes:", error);
    }
});

onUnmounted(() => {
  window.removeEventListener("resize", calculateFontSize);
});

</script>

<template>
    <div class=" w-full relative ">

        <div v-if="fontPreview" id="preview" class="fixed w-screen h-screen flex justify-center pt-14 bg-violet-200 z-50">
            <button @click="handleClose" class="absolute top-4 right-12 font-semibold">Close X</button>
            <div ref="card" class="relative flex flex-col justify-center items-center bg-white w-11/12 h-5/6 " >
                <div class="absolute w-full flex justify-between top-0 px-5 sm:px-20 py-5 gap-3 z-50">
                    <div class="relative flex gap-2 text-slate-400 z-50">
                       
                                <button 
                                @mousedown="startDecreasing"
                                @mouseup="stopChanging"
                                @mouseleave="stopChanging" class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded">
                                -
                                </button>
                                <button
                                @mousedown="startIncreasing"
                                @mouseup="stopChanging"
                                @mouseleave="stopChanging" class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded">
                                +
                                </button>
                        
                    </div>
                        <div class="relative flex right-0 gap-2 text-slate-400">
                            <div class="">
                                <button :class="isItalic === false ?  'text-violet-500' : ''" @click="toggleItalic(false)">
                                    Aa
                                </button>
                                /
                                <button :class="isItalic === true ?  'text-violet-500 italic' : 'italic'" @click="toggleItalic(true)">
                                    Aa
                                </button>
    
                            </div>
                            |
                            <div>
                                <button :class="caseMode === 2 ?  'text-violet-500 ' : ''" @click="toggleCase(2)">
                                    aa
                                </button>
                                /
                                <button :class="caseMode === 3 ?  'text-violet-500 ' : ''" @click="toggleCase(3)">
                                    Aa
                                </button>
                                /
                                <button :class="caseMode === 1 ?  'text-violet-500 ' : ''" @click="toggleCase(1)">
                                    AA
                                </button>
    
                            </div>
                        </div>
                </div>
                <div class=" relative bg-red-400 flex flex-col justify-end text-center h-full w-full">
                    <input
                    type="text"
                    v-model="text"
                    maxlength="30"
                    :class="`${isItalic === true ? 'italic' : ''} absolute border-none focus:outline-none focus:ring-0 text-center overflow-visible h-full w-full max-w-full text-wrap`"
                    :style="{
                        fontFamily: fontPreview.styleName,
                        textTransform: textTransform,
                        fontStyle: fontStyle,
                        fontSize: fontSizePreview + 'px',
                    }"
                    />

                    <p class="reltive bottom-10 text-lg text-gray-600 z-50 mb-20">
                    {{ fontPreview.name }}
                    </p>
                </div>
            </div>
        </div>

        <div v-if="!fontPreview">
            <div id="tools" class=" text-sm relative flex flex-col gap-4 sm:gap-0 sm:flex-row justify-between px-5 pt-10 pb-5 text-slate-400 ml-auto">
                <div class="flex flex-col sm:flex-row gap-3 sm:gap-5">
                    <div class="flex gap-2 items-center">
                        <label>Category:</label>
                        <select v-model="selectedCategory" @change="filterFonts" class=" border-none focus:outline-none focus:ring-0">
                            <option value="">All</option>
                            <option v-for="(subcategories, category) in fonts" :key="category" :value="category">{{ category }}</option>
                        </select>
                    </div>
                    <div v-if="selectedCategory" class="flex gap-2 items-center">
                        <label>Subcategory:</label>
                        <select v-model="selectedSubCategory" @change="filterFonts" :disabled="!selectedCategory" class=" border-none focus:outline-none focus:ring-0">
                            <option value="">All</option>
                            <option v-for="(fonts, subCategory) in fonts[selectedCategory]" :key="subCategory" :value="subCategory">{{ subCategory }}</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3  justify-end  w-full items-center sm:mx-0">
                    <button class="absolute sm:relative left-3 sm:mr-10 flex items-center gap-2" @click="toggleSelect">
                        <img :src="isAllSelected ? 'check-slected.svg' : 'check-unselected.svg'" alt="check"
                            class=" max-w-6">
                        <p :class="`${isAllSelected ? 'text-violet-400 font-semibold' : ''} hidden sm:block`">Unselect all
                        </p>
                    </button>
                    <div class="">
    
                        <button :class="isItalic === false ?  'text-violet-500' : ''" @click="toggleItalic(false)">
                            Aa
                        </button>
                        /
                        <button :class="isItalic === true ?  'text-violet-500 italic' : 'italic'" @click="toggleItalic(true)">
                            Aa
                        </button>
    
                    </div>
                    |
                    <div class="flex justify-center gap-3 ">
                        <button :class="caseMode === 2 ?  'text-violet-500 ' : ''" @click="toggleCase(2)">
                            aa
                        </button>
                        /
                        <button :class="caseMode === 3 ?  'text-violet-500 ' : ''" @click="toggleCase(3)">
                            Aa
                        </button>
                        /
                        <button :class="caseMode === 1 ?  'text-violet-500 ' : ''" @click="toggleCase(1)">
                            AA
                        </button>
                    </div>
                </div>
            </div>
    
            <div id="Mobile" class="sm:hidden flex flex-wrap xs:grid grid-cols-2 justify-around w-full gap-2 p-3">
                <div v-for="font in filteredFonts" :key="font.name"
                :class="`card w-full relative border rounded h-32 shadow-lg text-center flex flex-col gap-2 hover:scale-105 ${selectedFonts.includes(font.name) ? 'border-violet-400 shadow-violet-300' : ''}`">
                    <div class=" text-transparent hover:text-slate-400  absolute w-full flex  top-0 justify-between  px-2 py-1 gap-3 text-xs ">
                        <button @click="handleSelect(font.name)">
                            <img :src="selectedFonts.includes(font.name) ? 'check-slected.svg' : 'check-unselected.svg'"
                                alt="check" class=" max-w-4">
                        </button>
                        <!-- <div class="relative flex flex-col right-0 text-xs">
                            <div class="">
                                <button @click="particularToggleItalic(false, font.styleName)">
                                    Aa
                                </button>
                                /
                                <button class="italic" @click="particularToggleItalic(true, font.styleName)">
                                    Aa
                                </button>
    
                            </div>
                            <div>
                                <button @click="particularToggleCase(2, font.styleName)">
                                    aa
                                </button>
                                /
                                <button @click="particularToggleCase(3, font.styleName)">
                                    Aa
                                </button>
                                /
                                <button @click="particularToggleCase(1, font.styleName)">
                                    AA
                                </button>
    
                            </div>
                        </div> -->
                    </div>
                    <div @click="handlePreview(font)" class=" cursor-pointer">
                        <p :ref="el => fontElements[font.styleName] = el"
                            :id="font.styleName"
                            :class="`text-3xl xs:text-2xl ${isItalic === true ? 'italic' : ''} relative top-2 px-12 text-wrap`"
                            :style="{ fontFamily: font.styleName, textTransform: textTransform,  fontStyle: fontStyle, fontSize: dynamicFontSize}">
                            {{ store.globalText }}
                        </p>
                        <p class="relative text-[12px] text-gray-600 top-7">{{ font.name }}</p>
                    </div>
                </div>
            </div>
    
            <div id="desktop" class="hidden sm:flex flex-wrap justify-around w-full gap-y-3 gap-x-2 px-5">
                <div v-for="(font, index) in filteredFonts" :key="font.name"
                    :class="`card w-fit relative border rounded h-52 shadow-lg px-5 text-center flex flex-col gap-4 hover:scale-105 ${selectedFonts.includes(font.name) ? 'border-violet-400 shadow-violet-300' : ''}`">
                    <div class=" text-transparent hover:text-slate-400  absolute w-full p-5 flex  top-0 justify-between px-5 gap-3 ">
                        <button @click="handleSelect(font.name)" class=" w-6">
                            <img :src="selectedFonts.includes(font.name) ? 'check-slected.svg' : 'check-unselected.svg'"
                                alt="check" class=" max-w-5">
                        </button>
                        <div class="relative flex gap-2 right-0 text-sm">
                            <div class="">
    
                                <button @click="particularToggleItalic(false, font.styleName)">
                                    Aa
                                </button>
                                /
                                <button class="italic" @click="particularToggleItalic(true, font.styleName)">
                                    Aa
                                </button>
    
                            </div>
                            |
                            <button @click="particularToggleCase(2, font.styleName)">
                                aa
                            </button>
                            /
                            <button @click="particularToggleCase(3, font.styleName)">
                                Aa
                            </button>
                            /
                            <button @click="particularToggleCase(1, font.styleName)">
                                AA
                            </button>
                        </div>
                    </div>
                    <div @click="handlePreview(font)" class=" cursor-pointer">
                        <p :ref="el => fontElements[font.styleName] = el"
                            :id="font.styleName"
                            :class="`${font.styleName === 'Adelicia' ? 'text-4xl mt-3 max' : 'text-5xl'} ${isItalic === true ? 'italic' : ''} relative top-2 px-12 text-wrap`"
                            :style="{ fontFamily: font.styleName, textTransform: textTransform,  fontStyle: fontStyle, fontSize: dynamicFontSize}">
                            {{ text }}
                        </p>
                        <p class="relative top-12 text-sm text-gray-600">{{ font.name }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>


<style>
.card {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100px;
    max-height: 250px;
    overflow: hidden;
}
</style>