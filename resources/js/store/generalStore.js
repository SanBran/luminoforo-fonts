import { defineStore } from "pinia";

export const useGeneralStore = defineStore('generalStore', {
    state: () => {
        return {
            globalText: "Luminoforo"
        }
    },

    actions: {
        setGlobalText(value) {
            this.globalText = value
        }
    }
})