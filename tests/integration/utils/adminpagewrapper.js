const PageWrapper = require('./pagewrapper')
const puppeteer = require('puppeteer')

class AdminPageWrapper extends PageWrapper {

  constructor (browser) {
    super(browser)
  }

  async configureGroupCategories (competitionId) {
    await this.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=CompetitionSettings&tuja_competition=${competitionId}`)

    await this.click('#tuja_tab_groups')

    const addGroupCategory = async (name, ruleSetName) => {
      await this.click('#tuja_add_group_category_button_' + ruleSetName)
      const groupCategoryForm = await this.page.$('div.tuja-groupcategory-form:last-of-type')
      const groupCategoryName = await groupCategoryForm.$('input[type=text][name^="groupcategory__name__"]')
      await groupCategoryName.type(name)
    }

    const crewGroupCategoryName = 'The Crew'
    await addGroupCategory('Young Participants', 'YoungParticipantsRuleSet')
    await addGroupCategory('Old Participants', 'OlderParticipantsRuleSet')
    await addGroupCategory(crewGroupCategoryName, 'CrewMembersRuleSet')

    await this.clickLink('#tuja_save_competition_settings_button')
    return await this.$eval('input[type="text"][value="' + crewGroupCategoryName + '"]', node => node.name.substr('groupcategory__name__'.length))
  }

  async configureDefaultGroupStatus (competitionId, status) {
    await this.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=CompetitionSettings&tuja_competition=${competitionId}`)

    await this.click('#tuja_tab_groups')

    await this.click(`#tuja_competition_settings_initial_group_status-${status}`)

    await this.clickLink('#tuja_save_competition_settings_button')
  }

  async configureEventDateLimits (competitionId, startMinutes, endMinutes) {
    await this.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=CompetitionSettings&tuja_competition=${competitionId}`)

    await this.setDateInput('#tuja_event_start', startMinutes * 60)
    await this.setDateInput('#tuja_event_end', endMinutes * 60)

    await this.clickLink('#tuja_save_competition_settings_button')
  }

  async configureFormDateLimits (competitionId, formId, startMinutes, endMinutes) {
    await this.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Form&tuja_competition=${competitionId}&tuja_form=${formId}`)

    await this.setDateInput('#tuja-submit-response-start', startMinutes * 60)
    await this.setDateInput('#tuja-submit-response-end', endMinutes * 60)

    await this.clickLink('button[name="tuja_action"][value="form_update"]')
  }

  async init () {
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