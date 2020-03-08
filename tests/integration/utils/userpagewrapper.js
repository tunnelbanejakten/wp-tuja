const PageWrapper = require('./pagewrapper')
const puppeteer = require('puppeteer')

class UserPageWrapper extends PageWrapper {
  // competitionId
  // competitionKey

  constructor (browser, competitionId, competitionKey) {
    super(browser)
    this.competitionId = competitionId
    this.competitionKey = competitionKey
  }

  async init () {
    await super.init()

    // Device emulator list: https://github.com/puppeteer/puppeteer/blob/master/lib/DeviceDescriptors.js
    await this.page.emulate(puppeteer.devices['iPhone 6 Plus'])

    return this
  }

  async signUpTeam (adminPage, isAutomaticallyAccepted = true, isGroupLeader = true) {
    await adminPage.configureEventDateLimits(this.competitionId, 7 * 24 * 60, 7 * 24 * 60 + 60)
    const name = [
      ['The', 'Awesome', 'Five', 'Chill'],
      ['Moose', 'Wolves', 'Bears', 'Lynx', 'Foxes', 'Wolverines', 'Boars', 'Otters', 'Lemmings'],
      ['of', 'from'],
      ['Norrmalm', 'Gothenburg', 'Nacka', 'Kungsholmen', 'Solna', 'Farsta']
    ].map(options => options[Math.floor(Math.random() * options.length)]).join(' ')

    await this.goto(`http://localhost:8080/${this.competitionKey}/anmal`)
    await this.click('#tuja-group__age-0')
    await this.type('#tuja-group__name', name)
    await this.type('#tuja-person__email', 'amber@example.com')
    if (isGroupLeader) {
      await this.type('#tuja-person__name', 'Amber')
      await this.type('#tuja-person__phone', '070-123456')
      await this.type('#tuja-person__pno', '19800101-1234')
    } else {
      await this.click('#tuja-person__role-1')
    }
    if (isAutomaticallyAccepted) {
      await this.expectToContain('#tuja_signup_button', 'Anmäl lag')
    } else {
      await this.expectToContain('#tuja_signup_button', 'Anmäl lag till väntelista')
      await this.expectWarningMessage('Varför väntelista?')
    }
    await this.clickLink('#tuja_signup_button')
    if (isAutomaticallyAccepted) {
      await this.expectSuccessMessage('Tack')
    } else {
      await this.expectWarningMessage('Ert lag står på väntelistan')
    }
    const groupPortalLinkNode = await this.$('#tuja_group_home_link')
    const portalUrl = await groupPortalLinkNode.evaluate(node => node.href)
    const key = await groupPortalLinkNode.evaluate(node => node.dataset.groupKey)
    const id = await groupPortalLinkNode.evaluate(node => node.dataset.groupId)
    return { key, id, portalUrl, name }
  }

}

module.exports = UserPageWrapper