const AdminPageWrapper = require('./utils/adminpagewrapper')
const UserPageWrapper = require('./utils/userpagewrapper')

let adminPage = null
let defaultPage = null

const $$eval = async (selector, func) => defaultPage.$$eval(selector, func)
const type = async (selector, value) => defaultPage.type(selector, value)
const goto = async (url, waitForNetwork = false) => defaultPage.goto(url, waitForNetwork)
const clickLink = async (selector) => defaultPage.clickLink(selector)
const expectSuccessMessage = async (expected) => defaultPage.expectSuccessMessage(expected)
const expectErrorMessage = async (expected) => defaultPage.expectErrorMessage(expected)
const expectElementCount = async (selector, expectedCount) => defaultPage.expectElementCount(selector, expectedCount)

jest.setTimeout(300000)

describe('Tickets', () => {

  let competitionId = null
  let competitionKey = null
  let competitionName = null

  beforeAll(async () => {
    competitionId = global.competitionId
    competitionKey = global.competitionKey
    competitionName = global.competitionName
    adminPage = await (new AdminPageWrapper(browser).init())
    defaultPage = await (new UserPageWrapper(browser, competitionId, competitionKey).init())
  })

  afterAll(async () => {
    await adminPage.close()
    await defaultPage.close()
  })

  describe('Tickets', () => {

    let stationsProps = null

    beforeAll(async () => {
      //
      // Configure stations
      //

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Stations&tuja_competition=${competitionId}`)

      //
      // Configure ticketing
      //

      stationsProps = await global.stationIds.map(id => ({ id }))

      stationsProps[0].word = 'BLUEBERRY'
      stationsProps[1].word = 'RASPBERRY'
      stationsProps[2].word = 'STRAWBERRY'
      stationsProps[3].word = 'CLOUDBERRY'
      stationsProps[0].colour = '#b7fda0'
      stationsProps[1].colour = '#fdd9a8'
      stationsProps[2].colour = '#b0eefd'
      stationsProps[3].colour = '#fdbcc0'
      stationsProps[0].password = 'treasure'
      stationsProps[1].password = 'gold'
      stationsProps[2].password = 'abcåäö'
      stationsProps[3].password = 'winnings'

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=StationsTicketing&tuja_competition=${competitionId}`)

      await adminPage.page.$eval(`input[name="tuja_ticketdesign__${stationsProps[0].id}__colour"]`, (node, color) => node.value = color, stationsProps[0].colour)
      await adminPage.page.$eval(`input[name="tuja_ticketdesign__${stationsProps[1].id}__colour"]`, (node, color) => node.value = color, stationsProps[1].colour)
      await adminPage.page.$eval(`input[name="tuja_ticketdesign__${stationsProps[2].id}__colour"]`, (node, color) => node.value = color, stationsProps[2].colour)
      await adminPage.page.$eval(`input[name="tuja_ticketdesign__${stationsProps[3].id}__colour"]`, (node, color) => node.value = color, stationsProps[3].colour)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[0].id}__word"]`, stationsProps[0].word)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[1].id}__word"]`, stationsProps[1].word)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[2].id}__word"]`, stationsProps[2].word)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[3].id}__word"]`, stationsProps[3].word)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[0].id}__password"]`, stationsProps[0].password)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[1].id}__password"]`, stationsProps[1].password)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[2].id}__password"]`, stationsProps[2].password)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[3].id}__password"]`, stationsProps[3].password)

      await adminPage.type(`input[name="tuja_ticketcouponweight__${stationsProps[0].id}__${stationsProps[1].id}"]`, '1')
      await adminPage.type(`input[name="tuja_ticketcouponweight__${stationsProps[0].id}__${stationsProps[2].id}"]`, '2')
      await adminPage.type(`input[name="tuja_ticketcouponweight__${stationsProps[0].id}__${stationsProps[3].id}"]`, '3')
      await adminPage.type(`input[name="tuja_ticketcouponweight__${stationsProps[1].id}__${stationsProps[2].id}"]`, '4')
      await adminPage.type(`input[name="tuja_ticketcouponweight__${stationsProps[1].id}__${stationsProps[3].id}"]`, '5')
      await adminPage.type(`input[name="tuja_ticketcouponweight__${stationsProps[2].id}__${stationsProps[3].id}"]`, '6')

      await adminPage.clickLink('button[name="tuja_action"][value="save"]')
    })

    it('get tickets', async () => {
      const groupAliceProps = await defaultPage.signUpTeam(adminPage)
      const groupBobProps = await defaultPage.signUpTeam(adminPage)

      await adminPage.configureEventDateLimits(competitionId, -1, 10) // Event ends in 10 minutes

      //
      // Verify that Alice's team has no tickets.
      //

      await goto(`http://localhost:8080/${groupAliceProps.key}/biljetter`)
      await expectElementCount('div.tuja-ticket', 0)

      //
      // Verify that Bob's team has no tickets.
      //

      await goto(`http://localhost:8080/${groupBobProps.key}/biljetter`)
      await expectElementCount('div.tuja-ticket', 0)

      //
      // Bob's team uses their first password and gets their first two tickets.
      //

      await goto(`http://localhost:8080/${groupBobProps.key}/biljetter`)
      await type('#tuja_ticket_password', stationsProps[0].password)
      await clickLink('#tuja_validate_ticket_button')

      await expectSuccessMessage('Ni har fått 2 nya biljetter.')
      await expectElementCount('div.tuja-ticket', 2)
      const initialTicketWordsForTeamBob = await $$eval('div.tuja-ticket-word', nodes => nodes.map(node => node.textContent))
      expect(initialTicketWordsForTeamBob).toHaveLength(2)
      expect(initialTicketWordsForTeamBob.includes(stationsProps[0].word)).toBeFalsy()
      expect(initialTicketWordsForTeamBob.includes(stationsProps[1].word)).toBeTruthy()
      expect(initialTicketWordsForTeamBob.includes(stationsProps[2].word) ? !initialTicketWordsForTeamBob.includes(stationsProps[3].word) : initialTicketWordsForTeamBob.includes(stationsProps[3].word)).toBeTruthy()

      //
      // Bob's team tried to cheat and use their first password once more.
      //

      await goto(`http://localhost:8080/${groupBobProps.key}/biljetter`)
      await type('#tuja_ticket_password', stationsProps[0].password)
      await clickLink('#tuja_validate_ticket_button')
      await expectErrorMessage(`Cannot use ${stationsProps[0].password} twice`)
      await expectElementCount('div.tuja-ticket', 2)
      const ticketWordsAfterRetry = await $$eval('div.tuja-ticket-word', nodes => nodes.map(node => node.textContent))
      expect(ticketWordsAfterRetry).toEqual(initialTicketWordsForTeamBob)

      //
      // Alice's team uses the same code as Bob's team used (once successfully and once to cheat)
      //

      await goto(`http://localhost:8080/${groupAliceProps.key}/biljetter`)
      await expectElementCount('div.tuja-ticket', 0)
      await type('#tuja_ticket_password', stationsProps[0].password)
      await clickLink('#tuja_validate_ticket_button')
      await expectSuccessMessage('Ni har fått 2 nya biljetter.')
      await expectElementCount('div.tuja-ticket', 2)
      const initialTicketWordsForTeamAlice = await $$eval('div.tuja-ticket-word', nodes => nodes.map(node => node.textContent))
      expect(initialTicketWordsForTeamAlice).toHaveLength(2)
      expect(initialTicketWordsForTeamAlice.includes(stationsProps[0].word)).toBeFalsy()
      expect(initialTicketWordsForTeamAlice.includes(stationsProps[1].word)).toBeTruthy()
      expect(initialTicketWordsForTeamAlice.includes(stationsProps[2].word) ? !initialTicketWordsForTeamAlice.includes(stationsProps[3].word) : initialTicketWordsForTeamAlice.includes(stationsProps[3].word)).toBeTruthy()

      //
      // Bob's team uses their second password and gets the final two tickets.
      //

      await goto(`http://localhost:8080/${groupBobProps.key}/biljetter`)
      await type('#tuja_ticket_password', stationsProps[1].password)
      await clickLink('#tuja_validate_ticket_button')
      await expectSuccessMessage('Ni har fått 2 nya biljetter.')
      const finalTicketWords = await $$eval('div.tuja-ticket-word', nodes => nodes.map(node => node.textContent))
      expect(finalTicketWords).toHaveLength(4)
      expect(finalTicketWords.includes(stationsProps[0].word)).toBeTruthy()
      expect(finalTicketWords.includes(stationsProps[1].word)).toBeTruthy()
      expect(finalTicketWords.includes(stationsProps[2].word)).toBeTruthy()
      expect(finalTicketWords.includes(stationsProps[3].word)).toBeTruthy()

      //
      // Bob's team uses their third password but cannot get any more tickets.
      //

      await goto(`http://localhost:8080/${groupBobProps.key}/biljetter`)
      await type('#tuja_ticket_password', stationsProps[2].password)
      await clickLink('#tuja_validate_ticket_button')
      await expectSuccessMessage('Ni har fått 0 nya biljetter.')
      const finalRetryTicketWords = await $$eval('div.tuja-ticket-word', nodes => nodes.map(node => node.textContent))
      expect(finalRetryTicketWords).toHaveLength(4)
      expect(finalRetryTicketWords.includes(stationsProps[0].word)).toBeTruthy()
      expect(finalRetryTicketWords.includes(stationsProps[1].word)).toBeTruthy()
      expect(finalRetryTicketWords.includes(stationsProps[2].word)).toBeTruthy()
      expect(finalRetryTicketWords.includes(stationsProps[3].word)).toBeTruthy()

      //
      // Verify that Alice's team (still) has two tickets.
      //

      await goto(`http://localhost:8080/${groupAliceProps.key}/biljetter`)
      await expectElementCount('div.tuja-ticket', 2)
    })

    it('should not care about capitalization of password', async () => {
      const groupAliceProps = await defaultPage.signUpTeam(adminPage)

      await adminPage.configureEventDateLimits(competitionId, -1, 10) // Event ends in 10 minutes

      await goto(`http://localhost:8080/${groupAliceProps.key}/biljetter`)

      //
      // Start by verifying that invalid passwords are rejected with an error message
      //

      const invalidPassword = 'invalid' + stationsProps[0].password
      await type('#tuja_ticket_password', invalidPassword)
      await clickLink('#tuja_validate_ticket_button')
      await expectErrorMessage(`The password ${invalidPassword} is not correct`)

      //
      // Try a couple of valid passwords
      //

      const validPasswords = [
        'TREAsure',
        '  gold',
        'abcÅÄÖ  ',
        'abcåäö  '
      ]
      for (const password of validPasswords) {
        await type('#tuja_ticket_password', password)
        await clickLink('#tuja_validate_ticket_button')

        await expectSuccessMessage('Ni har fått') // We don't care about the number of tickets, just that we don't get an error
      }
    })

    it('should not be possible to use ticket function before the event', async () => {
      const groupProps = await defaultPage.signUpTeam(adminPage)

      await adminPage.configureEventDateLimits(competitionId, 10, 11) // Event begins in 10 minutes

      await goto(`http://localhost:8080/${groupProps.key}/biljetter`)

      await expectErrorMessage('Tävlingen har inte öppnat än.')
      await expectElementCount('form', 0)
      await expectElementCount('button', 0)
      await expectElementCount('input', 0)
      await expectElementCount('select', 0)
    })
  })
})
