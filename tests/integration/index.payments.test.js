const AdminPageWrapper = require('./utils/adminpagewrapper')
const UserPageWrapper = require('./utils/userpagewrapper')

let adminPage = null

jest.setTimeout(300000)

describe('Stations', () => {

  let competitionId = null
  let competitionKey = null
  let competitionName = null
  let stationIds = null

  beforeAll(async () => {
    competitionId = global.competitionId
    competitionKey = global.competitionKey
    competitionName = global.competitionName
    stationIds = global.stationIds
    adminPage = await (new AdminPageWrapper(browser).init())
  })

  afterAll(async () => {
    await adminPage.close()
  })

  describe('Payments', () => {
    it('import transactions', async () => {
      const addTeamMember = async (groupId, name, phone) => {
        await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=GroupMember&tuja_competition=${competitionId}&tuja_group=${groupId}&tuja_person=new`)
        await adminPage.type('#tuja_person_property__name', name)
        await adminPage.type('#tuja_person_property__phone', phone)
        await adminPage.type('#tuja_person_property__pno', '20050101')
        await adminPage.takeScreenshot()
        await adminPage.clickLink('button[name="tuja_action"][value="save"]')
        await adminPage.takeScreenshot()
      }

      const groupAliceProps = await adminPage.addTeam()
      const aliceName = 'Alice Alison'
      const alicePhone = '+467000' + Math.random().toFixed(5).substring(2)
      await addTeamMember(groupAliceProps.id, aliceName, alicePhone)

      const groupBobProps = await adminPage.addTeam()
      const bobName = 'Bob Barney Barley'
      const bobPhone = '+467000' + Math.random().toFixed(5).substring(2)
      await addTeamMember(groupBobProps.id, bobName, bobPhone)

      const groupCarolProps = await adminPage.addTeam()
      const carolName = 'Carol Coleson'
      const carolPhone = '+467000' + Math.random().toFixed(5).substring(2)
      await addTeamMember(groupCarolProps.id, carolName, carolPhone)
      await addTeamMember(groupBobProps.id, carolName, carolPhone) // Carol is in two teams.

      const invalidGroupKey = groupAliceProps.key.substring(0,5) + groupBobProps.key.substring(5)
      const invalidPhone = '+467000' + Math.random().toFixed(5).substring(2)

      // Goto Payments
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=PaymentsList&tuja_competition=${competitionId}`)
      await adminPage.expectElementCount('#tuja_transctions_list tbody tr', 0)

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=PaymentsImport&tuja_competition=${competitionId}`)
      const data = [
        {
          message: `TSL23 ${groupAliceProps.key} ${groupAliceProps.name}`,
          phone: alicePhone,
          sender: aliceName,
          amount: '100.00',

          expectedAmount: '100',
          expectedExplanation: `Laget har id ${groupAliceProps.key} och transaktionen nämner ${groupAliceProps.key}.`,
          expectedMatchGroupId: groupAliceProps.id
        },
        {
          message: `TSL23 ${groupBobProps.key.toUpperCase()} ${groupBobProps.name.toUpperCase()}`,
          phone: bobPhone,
          sender: bobName,
          amount: '200.00',

          expectedAmount: '200',
          expectedExplanation: `Laget har id ${groupBobProps.key} och transaktionen nämner ${groupBobProps.key.toUpperCase()}.`,
          expectedMatchGroupId: groupBobProps.id
        },
        {
          message: `TSL23 ${groupBobProps.key} ${groupBobProps.name}`,
          phone: carolPhone,
          sender: carolName,
          amount: '300.00',

          expectedAmount: '300',
          expectedExplanation: `Laget har id ${groupBobProps.key} och transaktionen nämner ${groupBobProps.key}.`,
          expectedMatchGroupId: groupBobProps.id // Bob's key in message is more important than Carol's phone number
        },
        {
          message: `Tunnelbanejakten ${groupBobProps.name}`,
          phone: bobPhone,
          sender: bobName,
          amount: '400.00',

          expectedAmount: '400',
          expectedExplanation: `Lagets deltagare ${bobName} har telefonnummer ${bobPhone} och transaktionen kommer från ${bobPhone}.`,
          expectedMatchGroupId: groupBobProps.id // Bob's phone number should be used
        },
        {
          message: `Saknas`,
          phone: carolPhone,
          sender: carolName,
          amount: '500.00',

          expectedAmount: '500',
          expectedExplanation: `Ingen matchning gjordes eftersom 2 personer har telefonnummer ${carolPhone}.`,
          expectedMatchGroupId: '0' // Carol's phone number doesn't uniquely identify one team.
        },
        {
          message: `TSL23 ${invalidGroupKey} Unknown Team`,
          phone: invalidPhone,
          sender: invalidPhone,
          amount: '600.00',

          expectedAmount: '600',
          expectedExplanation: `Ingen matchning gjordes eftersom 0 grupper har ID ${invalidGroupKey}.`,
          expectedMatchGroupId: '0' // Bob's key in message is more important than Carol's phone number
        },
      ]
      const lines = data.map(({ message, phone, sender, amount }, index) => `${index + 1},83279,7644370772,2023-04-11,2023-04-07,2023-04-11,1234154241,Tunnelbanejakten,${phone},${sender},${message},01:0${index},${amount},`)

      await adminPage.type(`#tuja_import_raw`, [
        '* Swish-rapport Avser 2023-01-01 - 2023-04-11 Skapad 2023-04-10 12:39 CEST',
        'Radnr,Clnr,Kontonr,Bokfdag,Transdag,Valutadag,Mottagarnr,Mottagarnamn,Avsändarnr,Avsändarnamn,Meddelande,Tid,Belopp,Orderreferens',
        ...lines].join('\n'))

      await adminPage.takeScreenshot()

      await adminPage.clickLink('button[name="tuja_action"][value="import_start"]')
      await adminPage.takeScreenshot()
      await adminPage.expectSuccessMessage('Importen gick bra')


      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=PaymentsMatch&tuja_competition=${competitionId}`)

      const matchedGroupIds = await adminPage.$$eval('select[name^=tuja_link_payment__]', nodes => nodes.map(node => node.value))
      const matchedAmounts = await adminPage.$$eval('input[name^=tuja_link_amount__]', nodes => nodes.map(node => node.value))
      const matchedTooltips = await adminPage.$$eval('span.tooltip-content', nodes => nodes.map(node => node.innerText.trim()))

      await adminPage.takeScreenshot()

      data.forEach(({ expectedMatchGroupId, expectedAmount, expectedExplanation }, index) => {
        expect(matchedGroupIds[index]).toBe(expectedMatchGroupId)
        expect(matchedAmounts[index]).toBe(expectedAmount)
        expect(matchedTooltips[index]).toBe(expectedExplanation)
      })
    })
  })
})
