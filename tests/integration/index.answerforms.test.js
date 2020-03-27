const querystring = require('querystring')
const AdminPageWrapper = require('./utils/adminpagewrapper')
const UserPageWrapper = require('./utils/userpagewrapper')

let defaultPage = null
let adminPage = null

const $ = async (selector, func) => defaultPage.$(selector, func)
const $eval = async (selector, func) => defaultPage.$eval(selector, func)
const type = async (selector, value) => defaultPage.type(selector, value)
const goto = async (url, waitForNetwork = false) => defaultPage.goto(url, waitForNetwork)
const chooseFiles = async (url, waitForNetwork = false) => defaultPage.chooseFiles(url, waitForNetwork)
const clickLink = async (selector) => defaultPage.clickLink(selector)
const expectSuccessMessage = async (expected) => defaultPage.expectSuccessMessage(expected)
const expectWarningMessage = async (expected) => defaultPage.expectWarningMessage(expected)
const expectErrorMessage = async (expected) => defaultPage.expectErrorMessage(expected)
const expectFormValue = async (selector, expected) => defaultPage.expectFormValue(selector, expected)
const expectElementCount = async (selector, expectedCount) => defaultPage.expectElementCount(selector, expectedCount)

describe('wp-tuja', () => {

  let competitionId = null
  let competitionKey = null
  let competitionName = null
  let crewGroupCategoryId = null

  const createNewUserPage = async () => (new UserPageWrapper(browser, competitionId, competitionKey)).init()

  const createNewForm = async (formName) => {
    await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Competition&tuja_competition=${competitionId}`)

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
    return formIds
  }

  beforeAll(async () => {

    jest.setTimeout(300000)

    competitionId = global.competitionId
    competitionKey = global.competitionKey
    competitionName = global.competitionName
    crewGroupCategoryId = global.crewGroupCategoryId
    adminPage = await (new AdminPageWrapper(browser).init())
    defaultPage = await (new UserPageWrapper(browser, competitionId, competitionKey).init())
  })

  describe('Answering forms', () => {

    let formKey = 0
    let formId = 0
    let groupProps = null

    const createForm = async () => {
      const {id, key} = await createNewForm('The Form')

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Form&tuja_competition=${competitionId}&tuja_form=${id}`)

      await adminPage.clickLink('button[name="tuja_action"][value="question_group_create"]')
      await adminPage.clickLink('div.tuja-admin-question a[href*="FormQuestions"]')
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__text"]')
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__number"]')
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__images"]')
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__choices"]')

      return ({id, key})
    }

    beforeAll(async () => {
      groupProps = await defaultPage.signUpTeam(adminPage)

      const formIds = await createForm()
      formKey = formIds.key
      formId = formIds.id
    })

    it('should show the correct number of questions', async () => {
      await goto(`http://localhost:8080/${groupProps.key}/svara/${formKey}`)
      await expectElementCount('section.tuja-question-group', 1)
      await expectElementCount('div.tuja-question', 4)
      await expectElementCount('input.tuja-fieldtext[type="text"]', 1)
      await expectElementCount('input.tuja-fieldtext[type="number"]', 1)
      await expectElementCount('div.tuja-image', 1)
      await expectElementCount('input.tuja-fieldchoices[type="radio"]', 3)
      await expectElementCount('button[name="tuja_formshortcode__action"][value="update"]', 1)
    })

    it('should be possible to answer radio button questions', async () => {
      await goto(`http://localhost:8080/${groupProps.key}/svara/${formKey}`)
      const option = await $('input.tuja-fieldchoices[type="radio"]')
      await option.click()
      const id = await option.evaluate(node => node.id)

      await clickLink('button[name="tuja_formshortcode__action"][value="update"]')
      expect(await $eval('#' + id, node => node.checked)).toBeTruthy()
    })

    it('should be possible to answer free-text questions', async () => {
      await goto(`http://localhost:8080/${groupProps.key}/svara/${formKey}`)
      await type('input.tuja-fieldtext[type="text"]', 'our answer')

      await clickLink('button[name="tuja_formshortcode__action"][value="update"]')
      await expectFormValue('input.tuja-fieldtext[type="text"]', 'our answer')
    })

    it('should be possible to answer number questions', async () => {
      await goto(`http://localhost:8080/${groupProps.key}/svara/${formKey}`)
      await type('input.tuja-fieldtext[type="number"]', '42')

      await clickLink('button[name="tuja_formshortcode__action"][value="update"]')
      await expectFormValue('input.tuja-fieldtext[type="number"]', '42')
    })

    it('should be possible to answer upload-image questions', async () => {
      const getFileUploadFieldsData = async () => defaultPage.$$eval(
        'div.tuja-image input[type="hidden"][name^="tuja_formshortcode__response__"][name$="[images][]"][value$=".jpeg"]',
        nodes => nodes.map(node => ({
          fileName: node.value,
          fileDigest: node.value.split(/\./)[0],
          thumbnailName: node.dataset.thumbnailUrl
        })))

      const saveAndVerifyUploads = async (forceReload = false) => {
        const uploadData = await getFileUploadFieldsData()

        await clickLink('button[name="tuja_formshortcode__action"][value="update"]')
        await expectSuccessMessage('Era svar har sparats')

        if (forceReload) {
          await goto(`http://localhost:8080/${groupProps.key}/svara/${formKey}`, true)
        }

        for (const data of uploadData) {
          await expectElementCount('div.tuja-image input[type="hidden"][name*="tuja_formshortcode__response__"][value="' + data.fileName + '"]', 1)
          await expectElementCount('div.tuja-image-select img[src*="' + data.fileDigest + '"]', 1)
        }
        await expectElementCount('div.tuja-fieldimages div.dz-preview', uploadData.length)
      }

      const removeAllImages = async () => {
        const handles = await defaultPage.page.$$('div.dropzone button.remove-image')
        for (const handle of handles) {
          await handle.click()
        }
      }

      const expectImageCounter = async (msg) => defaultPage.expectToContain('.tuja-question-hint.tuja-fieldimages-counter', msg)

      //
      // Upload one image
      //

      await goto(`http://localhost:8080/${groupProps.key}/svara/${formKey}`)

      await expectImageCounter('Ni kan ladda upp 2 bilder här.')

      await chooseFiles(['pexels-photo-1578484.jpeg'])

      await expectElementCount('div.tuja-fieldimages div.dz-preview', 1)
      await expectImageCounter('Ni kan ladda upp ytterligare 1 bild.')

      await saveAndVerifyUploads(false)

      //
      // Remove uploaded images
      //
      await goto(`http://localhost:8080/${groupProps.key}/svara/${formKey}`, true)
      await removeAllImages()

      await expectElementCount('div.tuja-fieldimages div.dz-preview', 0)
      await expectImageCounter('Ni kan ladda upp 2 bilder här.')

      // Save and reload
      await saveAndVerifyUploads(true)

      await expectElementCount('div.tuja-fieldimages div.dz-preview', 0)

      //
      // Upload two images (but select 1+2 images)
      //

      await chooseFiles(['pexels-photo-1578484.jpeg', 'pexels-photo-2285996.jpeg'])
      await expectElementCount('div.dz-preview.dz-complete .dz-image img[alt="pexels-photo-1578484.jpeg"]', 1)
      await expectElementCount('div.dz-preview.dz-complete .dz-image img[alt="pexels-photo-2285996.jpeg"]', 1)
      await expectImageCounter('Ni har laddad upp så många bilder som ni får. Vill ni byta ut en bild måste ni först ta bort en.')

      // Save (and verify that both images are saved)
      await saveAndVerifyUploads(false)

      // Remove all images
      await removeAllImages()
      await expectImageCounter('Ni kan ladda upp 2 bilder här.')

      // Upload one image
      await chooseFiles(['pexels-photo-174667.jpeg'])
      await expectElementCount('div.dz-preview.dz-complete.dz-success .dz-image img', 1)
      await expectElementCount('.dz-image img[alt="pexels-photo-174667.jpeg"]', 1)
      await expectImageCounter('Ni kan ladda upp ytterligare 1 bild.')

      // Try to upload two additional images (only one will succeed)
      await chooseFiles(['pexels-photo-1578484.jpeg', 'pexels-photo-2285996.jpeg'])
      await expectElementCount('div.dz-preview.dz-complete.dz-success .dz-image img', 2)
      await expectElementCount('.dz-image img[alt="pexels-photo-1578484.jpeg"]', 1)
      await expectElementCount('.dz-image img[alt="pexels-photo-174667.jpeg"]', 1)
      await expectImageCounter('Ni har laddad upp så många bilder som ni får. Vill ni byta ut en bild måste ni först ta bort en.')

      // Save (and verify that only two images are saved)
      await saveAndVerifyUploads(false)

      //
      // Replace uploaded images two new one (selected one by one) and make sure the Dropzone is disabled after
      // picking the second one.
      //

      // Remove all images
      await removeAllImages()
      await expectElementCount('div.dz-preview.dz-complete.dz-success .dz-image img', 0)
      await expectImageCounter('Ni kan ladda upp 2 bilder här.')

      // Upload one image
      await chooseFiles(['pexels-photo-1578484.jpeg'])
      await expectElementCount('div.dz-preview.dz-complete.dz-success .dz-image img', 1)
      await expectImageCounter('Ni kan ladda upp ytterligare 1 bild.')

      // Upload one image more
      await chooseFiles(['pexels-photo-174667.jpeg'])
      await expectElementCount('div.dz-preview.dz-complete.dz-success .dz-image img', 2)
      await expectImageCounter('Ni har laddad upp så många bilder som ni får. Vill ni byta ut en bild måste ni först ta bort en.')

      // Save (and verify that both images are saved)
      await saveAndVerifyUploads(false)
    })

    it('should NOT be possible to SEE questions BEFORE form has been OPENED', async () => {
      await adminPage.configureFormDateLimits(competitionId, formId, 10, 20)

      await goto(`http://localhost:8080/${groupProps.key}/svara/${formKey}`)
      await expectWarningMessage('Formuläret kan inte visas just nu.')
      await expectElementCount('section.tuja-question-group', 0)
      await expectElementCount('div.tuja-question', 0)
      await expectElementCount('button[name="tuja_formshortcode__action"][value="update"]', 0)

      await adminPage.configureFormDateLimits(competitionId, formId, -10, 10)
    })

    it('should NOT be possible to update answers AFTER form has been CLOSED', async () => {
      await adminPage.configureFormDateLimits(competitionId, formId, -20, -10)

      await goto(`http://localhost:8080/${groupProps.key}/svara/${formKey}`)
      await expectErrorMessage('Svar får inte skickas in nu.')
      await expectElementCount('button[name="tuja_formshortcode__action"][value="update"]', 0)

      await adminPage.configureFormDateLimits(competitionId, formId, -10, 10)
    })

    it('should NOT be possible for two people in the same team to overwrite each others answers', async () => {
      const aliceSession = await createNewUserPage()
      const bobSession = await createNewUserPage()

      await aliceSession.goto(`http://localhost:8080/${groupProps.key}/svara/${formKey}`)
      await bobSession.goto(`http://localhost:8080/${groupProps.key}/svara/${formKey}`)

      // Alice answers first question only
      await aliceSession.type('input.tuja-fieldtext[type="text"]', 'The updated answer from Alice')
      await aliceSession.clickLink('button[name="tuja_formshortcode__action"][value="update"]')
      const expectedNumberAnswer = await aliceSession.$eval('input.tuja-fieldtext[type="number"]', node => node.value)

      await aliceSession.expectFormValue('input.tuja-fieldtext[type="text"]', 'The updated answer from Alice')

      // Bob answers first and second question. None of his answers should be kept.
      await bobSession.type('input.tuja-fieldtext[type="text"]', 'The answer from Bob')
      await bobSession.type('input.tuja-fieldtext[type="number"]', '1337')
      await bobSession.clickLink('button[name="tuja_formshortcode__action"][value="update"]')

      await bobSession.expectErrorMessage('Medan du fyllde i formuläret hann någon annan i ditt lag skicka in andra svar på några av frågorna.')

      // Alice's answer should still be there.
      await bobSession.expectFormValue('input.tuja-fieldtext[type="text"]', 'The updated answer from Alice')

      // Answer unchanged by Alice but changed by Bob should still be the original value since the logic does not know
      // if Alice cared about this value or not (we don't know if Alice wanted it to stay the same of if she's okay
      // with Bob changing it.)
      await bobSession.expectFormValue('input.tuja-fieldtext[type="number"]', expectedNumberAnswer)

      await aliceSession.close()
      await bobSession.close()
    })

    it('should be possible for two people in the same team to give the same answer concurrently', async () => {
      const aliceSession = await createNewUserPage()
      const bobSession = await createNewUserPage()

      // Alice sets the initial answers
      await aliceSession.goto(`http://localhost:8080/${groupProps.key}/svara/${formKey}`)
      await aliceSession.type('input.tuja-fieldtext[type="text"]', 'The answer')
      await aliceSession.clickLink('button[name="tuja_formshortcode__action"][value="update"]')

      await bobSession.goto(`http://localhost:8080/${groupProps.key}/svara/${formKey}`) // Load Bob's form to get up-to-date lock value

      // Alice changes the answer to the first question
      await aliceSession.type('input.tuja-fieldtext[type="text"]', 'The new answer')
      await aliceSession.clickLink('button[name="tuja_formshortcode__action"][value="update"]')

      // Bob writes the same answer to the first question. Bob's lock value is out-of-date at this point.
      await bobSession.type('input.tuja-fieldtext[type="text"]', 'The new answer')
      await bobSession.clickLink('button[name="tuja_formshortcode__action"][value="update"]')

      // Bob gets no error message, even though his lock value is out-of-date, since he doesn't actually change anything compared to the latest answers (i.e. the answer updated by Alice).
      await bobSession.expectElementCount('.tuja-message-error', 0)
      await bobSession.expectFormValue('input.tuja-fieldtext[type="text"]', 'The new answer')

      await aliceSession.close()
      await bobSession.close()
    })
  })
})
