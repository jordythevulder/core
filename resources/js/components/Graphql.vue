<script>
    import InteractWithUser from './User/mixins/InteractWithUser'

    export default {
        mixins: [InteractWithUser],

        props: {
            query: {
                type: String,
                required: true,
            },
            variables: {
                type: Object,
                default: () => ({}),
            },
            check: {
                type: String,
            },
            redirect: {
                type: String,
            },
            cache: {
                type: String,
            },
            callback: {
                type: Function,
            },
        },

        data: () => ({
            data: null,
            cachePrefix: 'graphql_'
        }),

        render() {
            return this.$scopedSlots.default({
                data: this.data,
                runQuery: this.runQuery
            })
        },

        created() {
            if (!this.getCache()) {
                this.runQuery()
            }
        },

        methods: {
            getCache() {
                let cache = false

                if (cache = localStorage[this.cachePrefix + this.cache]) {
                    this.data = JSON.parse(cache)
                }

                return cache
            },

            async runQuery() {
                try {
                    let options = { headers: {} }

                    if (this.$root.user) {
                        options['headers']['Authorization'] = `Bearer ${localStorage.token}`
                    }

                    if (window.config.store_code) {
                        options['headers']['Store'] = window.config.store_code
                    }

                    let response = await axios.post(config.magento_url + '/graphql', {
                        query: this.query,
                        variables: this.variables,
                    }, options)

                    if (response.data.errors) {
                        if (response.data.errors[0].extensions.category == 'graphql-authorization') {
                            this.logout('/login')
                        } else {
                            Notify(response.data.errors[0].message, 'error')
                        }
                        return
                    }

                    if (this.check) {
                        if (!eval('response.data.' + this.check)) {
                            Turbolinks.visit(this.redirect)
                            return
                        }
                    }

                    this.data = this.callback
                        ? await this.callback(response)
                        : response.data.data

                    if (this.cache) {
                        localStorage[this.cachePrefix + this.cache] = JSON.stringify(this.data)
                    }
                } catch (e) {
                    Notify(window.config.translations.errors.wrong, 'warning')
                }
            }
        }
    }
</script>
