const PageWrapper = require('./pagewrapper')
const puppeteer = require('puppeteer')

class AdminPageWrapper extends PageWrapper {

  constructor(browser) {
    super(browser)
  }

  async addTeam() {
    const name = 'Team ' + Math.random().toFixed(5).substring(2) // "Team" and 5 random digits
    await this.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=GroupsList&tuja_competition=${competitionId}`)

    await this.type('input[name=tuja_new_group_name]', name)

    await this.clickLink('button[name="tuja_action"][value="group_create"]')

    const { groupId, groupKey } = await this.$eval(
      `span#tuja_new_group_message`,
      node => ({
        groupId: node.dataset.groupId,
        groupKey: node.dataset.groupKey
      })
    )

    return {
      id: groupId,
      key: groupKey,
      name
    }
  }

  async configurePaymentDetails(competitionId) {
    await this.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=CompetitionSettingsFees&tuja_competition=${competitionId}`)

    await this.type('input[name*=fee]', '100') // The "change" event will not be triggered...
    await this.click('#wpbody-content') // ...until we click on something, anything, else.

    await this.clickLink('#tuja_save_competition_settings_button')
  }

  async configureDefaultGroupStatus(competitionId, status) {
    await this.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=CompetitionSettingsGroupLifecycle&tuja_competition=${competitionId}`)

    await this.click(`#tuja_competition_settings_initial_group_status-${status}`)

    await this.clickLink('#tuja_save_competition_settings_button')
  }

  async configureEventDateLimits(competitionId, startMinutes, endMinutes) {
    await this.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=CompetitionSettingsBasic&tuja_competition=${competitionId}`)

    await this.setDateInput('#tuja_event_start', startMinutes * 60)
    await this.setDateInput('#tuja_event_end', endMinutes * 60)

    await this.clickLink('#tuja_save_competition_settings_button')
  }

  async configureGroupCategoryDateLimits(competitionId, isYoungParticipantsCategoryOpenNow, isOldParticipantsCategoryOpenNow) {
    await this.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=CompetitionSettingsGroupCategories&tuja_competition=${competitionId}`)

    const setGroupCategoryDateRange = async (categoryName, includeNow, isNinOptional) => {
      const categoryId = await this.$eval('input[type="text"][value="' + categoryName + '"]', node => node.name.substr('groupcategory__name__'.length))

      await this.$eval('#groupcategory__rules__' + categoryId, (node, includeNow, isNinOptional) => {
        const now = new Date()
        const todayDateString = now.toISOString().substring(0, 10)
        const localTzOffsetSeconds = now.getTimezoneOffset() * 60
        const todayTimestamp = Date.parse(todayDateString) / 1000
        const todaySeconds = todayTimestamp - localTzOffsetSeconds
        const oneDay = 24 * 60 * 60

        const original = JSON.parse(node.value)
        node.value = JSON.stringify({
          ...original,
          create_registration_period_start: todaySeconds + (includeNow ? -1 : -2) * oneDay,
          create_registration_period_end: todaySeconds + (includeNow ? 0 : -1) * oneDay,
          leader_nin: 'nin_or_date_' + (isNinOptional ? 'optional' : 'required')
        })
      }, includeNow, isNinOptional)
    }

    await setGroupCategoryDateRange('Young Participants', isYoungParticipantsCategoryOpenNow, true)
    await setGroupCategoryDateRange('Old Participants', isOldParticipantsCategoryOpenNow, false)

    await this.clickLink('#tuja_save_competition_settings_button')
  }

  async configureGroupCategoryFoodRules(competitionId, categoryName, foodStrict) {
    await this.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=CompetitionSettingsGroupCategories&tuja_competition=${competitionId}`)

    const categoryId = await this.$eval('input[type="text"][value="' + categoryName + '"]', node => node.name.substr('groupcategory__name__'.length))

    const ruleValue = foodStrict ? 'fixed_options_and_custom' : 'optional';

    await this.$eval('#groupcategory__rules__' + categoryId, (node, ruleValue) => {
      const original = JSON.parse(node.value)
      node.value = JSON.stringify({
        ...original,
        leader_food: ruleValue,
        regular_food: ruleValue,
        supervisor_food: ruleValue,
        admin_food: ruleValue,
      })
    }, ruleValue)

    await this.clickLink('#tuja_save_competition_settings_button')
  }

  async configureFormDateLimits(competitionId, formId, startMinutes, endMinutes) {
    await this.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Form&tuja_competition=${competitionId}&tuja_form=${formId}`)

    await this.setDateInput('#tuja-submit-response-start', startMinutes * 60)
    await this.setDateInput('#tuja-submit-response-end', endMinutes * 60)

    await this.clickLink('button[name="tuja_action"][value="form_update"]')
  }

  async init() {
    await super.init()

    // Device emulator list: https://github.com/puppeteer/puppeteer/blob/master/lib/DeviceDescriptors.js
    await this.page.emulate(puppeteer.devices['iPad Pro landscape'])
    // Log in to Admin console
    await this.goto('http://localhost:8080/wp-admin', true)
    await this.type('#user_login', 'admin')
    await this.type('#user_pass', 'admin')
    await this.clickLink('#wp-submit')

    return this
  }
}

module.exports = AdminPageWrapper