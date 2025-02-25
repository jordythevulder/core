<script>
export default {
    props: {
        aggregations: Object,
        setQuery: Function,
        value: String,
    },

    render() {
        return this.$scopedSlots.default({
            hasResults: this.hasResults,
            results: this.results,
        })
    },

    data: () => ({
        results: [],
    }),

    created() {
        this.processValue(this.value)
    },

    watch: {
        value: function (value) {
            this.processValue(value)
        },
        hasResults: function (value) {
            if (!value) {
                return;
            }

            this.createCategoryPaths()
        }
    },

    methods: {
        processValue(value) {
            if (!value) {
                this.setQuery({})
                return
            }

            this.setQuery({
                query: { match_phrase: { 'category_paths': value } },
                value: value
            })
        },

        createCategoryPaths() {
            if (
                !this.aggregations?.category_paths?.buckets?.length ||
                !this.aggregations?.categories?.buckets?.length
            ) {
                this.results = []
                return;
            }

            // Calculating and unpacking the category structure is a heavy task.
            // Hand this down to a webworker thread to free the main thread.
            WebWorker.run(
                function (categories, allCategoryPaths, currentCategory) {
                    function getCategoryStructure(categories, allCategoryPaths, currentCategory) {
                        const lowestCategories = categories.map((bucket => {
                            const key = bucket.key.split(' /// ').pop()
                            let idAndLabel = key.split('::')
                            // Usually you'd use the Destructuring Assignment, in this case we dont want to.
                            // Since this will let the webpack polyfill kick in which is not available in the web worker.
                            let id = idAndLabel[0],
                                label = idAndLabel[1];

                            return {
                                id: id,
                                key: bucket.key,
                                structure: bucket.key.split(' /// '),
                                label: label,
                                doc_count: bucket.doc_count,
                            }
                        })).filter(bucket => getCategoryPaths(allCategoryPaths, currentCategory).find(path => path.key.includes(bucket.id)))

                        return buildCategoryStructure(lowestCategories).children;
                    }

                    function getCategoryPaths(allCategoryPaths, currentCategory) {
                        if (!currentCategory?.entity_id) {
                            return allCategoryPaths
                        }

                        // Get children of current category.
                        let categoryPaths = allCategoryPaths.filter(bucket => bucket.key.includes(String(currentCategory.entity_id))&& bucket.key.at(-1) !== String(currentCategory.entity_id))

                        if (categoryPaths.length > 1) {
                            return categoryPaths
                        }

                        // No child categories, get siblings instead.
                        let parentCategoryId = categoryPaths[0].key.at(-2)
                        return allCategoryPaths.filter(bucket => bucket.key.includes(parentCategoryId) && bucket.key.at(-1) !== parentCategoryId)
                    }

                    function buildCategoryStructure(categories, results = {children: {}}) {
                        categories.map(category => {
                            if (!category.structure.length) {
                                return
                            }

                            let key = category.structure.shift()
                            let idAndLabel = key.split('::')
                            let id = idAndLabel[0],
                                label = idAndLabel[1];

                            if(!results.children) {
                                results.children = {}
                            }

                            if (!results?.children?.hasOwnProperty(id)) {
                                results.children[id] = {
                                    id: id,
                                    key: key,
                                    label: label,
                                    structure: category.structure,
                                    doc_count: category.doc_count,
                                    children: {},
                                }
                            }

                            results.children[id] = buildCategoryStructure([category], results.children[id])
                        });

                        return results
                    }

                    return getCategoryStructure(categories, allCategoryPaths, currentCategory);
                },
                [
                    this.aggregations.categories.buckets,
                    this.aggregations.category_paths.buckets,
                    this.currentCategory
                ]
            )
            .then(categoryStructure => {
                this.results = categoryStructure
            });
        }
    },

    computed: {
        hasResults: function () {
            return this.aggregations?.categories?.buckets?.length
        },

        currentCategory: function () {
            return config.category
        },
    }
}
</script>
