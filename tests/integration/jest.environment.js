const PuppeteerEnvironment = require('jest-environment-puppeteer')

const initCompetition = require('./utils/initcompetition')

class CustomEnvironment extends PuppeteerEnvironment {
  async setup () {
    await super.setup()

    if (!process.env.competitionId) {
      const competitionData = await initCompetition(this.global.browser)

      process.env.competitionId = competitionData.id
      process.env.competitionKey = competitionData.key
      process.env.competitionName = competitionData.name
      process.env.crewGroupCategoryId = competitionData.crewGroupCategoryId
    }

    this.global.competitionId = process.env.competitionId
    this.global.competitionKey = process.env.competitionKey
    this.global.competitionName = process.env.competitionName
    this.global.crewGroupCategoryId = process.env.crewGroupCategoryId
  }

  async teardown () {
    await super.teardown()
  }
}

module.exports = CustomEnvironment
