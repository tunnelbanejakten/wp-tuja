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
      process.env.formKey = competitionData.formKey
      process.env.formId = competitionData.formId
    }

    this.global.competitionId = process.env.competitionId
    this.global.competitionKey = process.env.competitionKey
    this.global.competitionName = process.env.competitionName
    this.global.crewGroupCategoryId = process.env.crewGroupCategoryId
    this.global.formKey = process.env.formKey
    this.global.formId = process.env.formId
  }

  async teardown () {
    await super.teardown()
  }
}

module.exports = CustomEnvironment
