const AdminPageWrapper = require('./utils/adminpagewrapper')

let adminPage = null

jest.setTimeout(300000)

describe('Administration', () => {

  let competitionId = null

  beforeAll(async () => {
    competitionId = global.competitionId
    adminPage = await (new AdminPageWrapper(browser).init())
  })

  afterAll(async () => {
    await adminPage.close()
  })

  describe('Forms', () => {

    let formKey = 0
    let formId = 0

    beforeAll(async () => {
      formKey = global.formKey
      formId = global.formId
    })

    it('should be able to create a new form', async () => {
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Competition&tuja_competition=${competitionId}`)

      const formName = 'New Form'
      await adminPage.type('#tuja_form_name', formName)
      await adminPage.clickLink('#tuja_form_create_button')

      const links = await adminPage.page.$$('form.tuja a')
      let link = null
      for (let i = 0; i < links.length; i++) {
        const el = links[i]
        const linkText = await el.evaluate(node => node.innerText)
        const isEqual = linkText === formName
        if (isEqual) {
          link = el
          break
        }
      }

      const formIds = await link.evaluate(node => ({ id: node.dataset.id, key: node.dataset.randomId }))

      const { id, key } = formIds

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Form&tuja_competition=${competitionId}&tuja_form=${id}`)

      await adminPage.clickLink('button[name="tuja_action"][value="question_group_create"]')
      await adminPage.clickLink('div.tuja-admin-question a[href*="FormQuestions"]')
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__images"]')
      await adminPage.expectElementCount('div.tuja-admin-question-imagesquestion', 1)
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__text"]')
      await adminPage.expectElementCount('div.tuja-admin-question-textquestion', 1)
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__number"]')
      await adminPage.expectElementCount('div.tuja-admin-question-numberquestion', 1)
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__choices"]')
      await adminPage.expectElementCount('div.tuja-admin-question-optionsquestion', 1)
    })
  })
})
