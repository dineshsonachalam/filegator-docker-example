<script>
import api from '../../api/api'
import _ from 'lodash'

export default {
  name: 'Profile',
  data() {
    return {
      oldpassword: '',
      newpassword: '',
      formErrors: {},
    }
  },
  methods: {
    save() {
      api.changePassword({
        oldpassword: this.oldpassword,
        newpassword: this.newpassword,
      })
        .then(() => {
          this.$toast.open({
            message: this.lang('Updated'),
            type: 'is-success',
          })
          this.$parent.close()
        })
        .catch(errors => {
          if (typeof errors.response.data.data != 'object') {
            this.handleError(errors)
          }
          _.forEach(errors.response.data, err => {
            _.forEach(err, (val, key) => {
              this.formErrors[key] = this.lang(val)
              this.$forceUpdate()
            })
          })
        })
    },
  },
}
</script>
