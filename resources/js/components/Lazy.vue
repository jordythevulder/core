<!-- Copied from https://github.com/RadKod/v-lazy-component and modified it -->
<template>
    <component
        :is="state.wrapperTag"
        :class="[
            'v-lazy-component',
            {
                'v-lazy-component--loading': !state.isIntersected,
                'v-lazy-component--loaded': state.isIntersected,
            },
        ]"
        :style="{
            minWidth: '1px',
            minHeight: '1px',
        }"
    >
        <slot v-if="state.isIntersected" :intersected="state.isIntersected" />
        <!-- Content that is loaded as a placeholder until it comes into view -->
        <slot v-if="!state.isIntersected" name="placeholder" />
    </component>
</template>

<script>
export default {
    props: {
        wrapperTag: {
            type: String,
            required: false,
            default: 'div',
        },
        isIntersected: {
            type: Boolean,
            required: false,
            default: false,
        },
        idle: {
            type: Boolean,
            required: false,
            default: false,
        },
        /**
         * See IntersectionOberserver rootMargin [docs](https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API#Intersection_observer_options)
         */
        rootMargin: {
            type: String,
            required: false,
            default: '0px 0px 0px 0px',
        },
        /**
         * See IntersectionOberserver treshold [docs](https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API#Intersection_observer_options)
         */
        threshold: {
            type: [Number, Array],
            required: false,
            default: 0,
        },
    },
    data() {
        return {
            state: {
                wrapperTag: this.wrapperTag,
                isIntersected: this.isIntersected,
                idle: this.idle,
                rootMargin: this.rootMargin,
                threshold: this.threshold,
                observer: null,
            },
        };
    },
    watch: {
        isIntersected(value) {
            if (value) {
                this.state.isIntersected = true;
            }
        },
        'state.isIntersected'(value) {
            if (value) {
                this.$emit('intersected', this.$el);
            }
        },
    },
    mounted() {
        if ('IntersectionObserver' in window) {
            if (!this.state.isIntersected && !this.state.idle) {
                this.observe();
            }
        }

        this.onIdle(() => {
            this.state.isIntersected = true;
        })

        if(this.state.isIntersected) {
            this.$emit('intersected', this.$el);
        }
    },
    beforeDestroy() {
        if (!this.state.isIntersected && !this.state.idle) {
            this.unobserve();
        }
    },
    methods: {
        onIdle(callback) {
            setTimeout(() => {
                this.$nextTick(callback)
            }, 3000)
        },
        observe() {
            const { rootMargin, threshold } = this.state;
            const config = { root: undefined, rootMargin, threshold };
            this.state.observer = new IntersectionObserver(
                this.onIntersection,
                config
            );
            this.state.observer.observe(this.$el);
        },
        onIntersection(entries) {
            this.state.isIntersected = entries.some(
                (entry) => entry.intersectionRatio > 0
            );
            if (this.state.isIntersected) {
                this.unobserve();
            }
        },
        unobserve() {
            if ('IntersectionObserver' in window) {
                this.state.observer.unobserve(this.$el);
            }
        },
    },
};
</script>
