import ShowcasesPage from '../../support/page-objects/ShowcasesPage';

describe('Showcases', () => {
  const showcasesPage = new ShowcasesPage();

  beforeEach(() => {
    cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
    cy.intercept('GET', '/api/v1/showcases*', { fixture: 'showcases/list.json' }).as('getShowcases');
  });

  it('UC52: should list showcases', () => {
    showcasesPage.visit();
    cy.wait('@getShowcases');

    cy.get('#tbody tr').should('have.length', 2);
    cy.get('#tbody').should('contain.text', 'Destaques da Semana');
    cy.get('#tbody').should('contain.text', 'Mais Jogados');
  });

  it('UC53: should filter by status and type', () => {
    showcasesPage.visit();
    cy.wait('@getShowcases');

    cy.intercept('GET', '/api/v1/showcases*status=active*', {
      body: {
        data: [
          { id: 1, title: 'Destaques da Semana', slug: 'destaques-semana', position: 1, type: 'manual', status: 'active', slots_count: 6 },
        ],
        next_cursor: null,
        prev_cursor: null,
      },
    }).as('getShowcasesFiltered');

    cy.get('#status').select('active');
    cy.get('#btnSearch').click();
    cy.wait('@getShowcasesFiltered');

    cy.get('#tbody tr').should('have.length.at.least', 1);
  });

  it('UC54: should create a showcase (manual type)', () => {
    showcasesPage.visit();
    cy.wait('@getShowcases');

    cy.intercept('POST', '/api/v1/showcases', {
      statusCode: 201,
      body: { data: { id: 3, title: 'New Showcase', slug: 'new-showcase', type: 'manual', status: 'active' } },
    }).as('createShowcase');

    cy.intercept('GET', '/api/v1/showcases*', { fixture: 'showcases/list.json' }).as('reloadShowcases');

    showcasesPage.clickNew();
    cy.get('#editModal').should('be.visible');

    showcasesPage.fillTitle('New Showcase');
    showcasesPage.clickSave();

    cy.wait('@createShowcase');
    cy.get('.toast').should('contain.text', 'criado');
  });

  it('UC55: should edit a showcase', () => {
    showcasesPage.visit();
    cy.wait('@getShowcases');

    cy.intercept('GET', '/api/v1/showcases/1', {
      statusCode: 200,
      fixture: 'showcases/single.json',
    }).as('getShowcase');

    cy.intercept('PUT', '/api/v1/showcases/1', {
      statusCode: 200,
      body: { data: { id: 1, title: 'Destaques Updated', slug: 'destaques-semana', status: 'active' } },
    }).as('updateShowcase');

    cy.intercept('GET', '/api/v1/showcases*', { fixture: 'showcases/list.json' }).as('reloadShowcases');

    showcasesPage.clickEditOnRow(1);
    cy.get('#editModal').should('be.visible');

    cy.get('#f_title').clear().type('Destaques Updated');
    showcasesPage.clickSave();

    cy.wait('@updateShowcase');
    cy.get('.toast').should('contain.text', 'atualizado');
  });

  it('UC56: should manage slots of a showcase', () => {
    showcasesPage.visit();
    cy.wait('@getShowcases');

    cy.intercept('GET', '/api/v1/showcases/1', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          title: 'Destaques da Semana',
          slots: [{ id: 1, title: 'Sweet Bonanza', pivot: { position: 1 } }],
        },
      },
    }).as('getShowcaseSlots');

    cy.intercept('PUT', '/api/v1/showcases/1/slots', {
      statusCode: 200,
      body: { message: 'Slots sincronizados.' },
    }).as('syncShowcaseSlots');

    showcasesPage.clickSlotsOnRow(1);
    cy.get('.modal').should('be.visible');
  });

  it('UC57: should delete a showcase', () => {
    showcasesPage.visit();
    cy.wait('@getShowcases');

    cy.intercept('DELETE', '/api/v1/showcases/2', {
      statusCode: 200,
      body: {},
    }).as('deleteShowcase');

    cy.intercept('GET', '/api/v1/showcases*', { fixture: 'showcases/list.json' }).as('reloadShowcases');

    cy.on('window:confirm', () => true);

    showcasesPage.clickDeleteOnRow(2);

    cy.wait('@deleteShowcase');
    cy.get('.toast').should('contain.text', 'excluído');
  });
});
