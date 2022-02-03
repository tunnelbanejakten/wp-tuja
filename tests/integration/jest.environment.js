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
      process.env.crewGroupKey = competitionData.crewGroupKey
      process.env.formKey = competitionData.formKey
      process.env.formId = competitionData.formId
      process.env.mapId = competitionData.mapId
      process.env.stationIds = competitionData.stationIds
    }

    this.global.competitionId = process.env.competitionId
    this.global.competitionKey = process.env.competitionKey
    this.global.competitionName = process.env.competitionName
    this.global.crewGroupKey = process.env.crewGroupKey
    this.global.formKey = process.env.formKey
    this.global.formId = process.env.formId
    this.global.mapId = process.env.mapId
    this.global.stationIds = process.env.stationIds.split(',')
  }

  async teardown () {
    await super.teardown()
  }
}

module.exports = CustomEnvironment
