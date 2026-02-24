import CategoriesPage from '../../support/page-objects/CategoriesPage';

describe('Categories', () => {
  const categoriesPage = new CategoriesPage();

  beforeEach(() => {
    cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
    cy.intercept('GET', '/api/v1/categories*', { fixture: 'categories/list.json' }).as('getCategories');
  });

  it('UC35: should list categories', () => {
    categoriesPage.visit();
    cy.wait('@getCategories');

    cy.get('#tbody tr').should('have.length', 3);
    cy.get('#tbody').should('contain.text', 'Populares');
    cy.get('#tbody').should('contain.text', 'Novidades');
    cy.get('#tbody').should('contain.text', 'Top 10 Semana');
  });

  it('UC36: should filter by vertical, type, and status', () => {
    categoriesPage.visit();
    cy.wait('@getCategories');

    cy.intercept('GET', '/api/v1/categories*vertical=slots*', {
      body: {
        data: [
          { id: 1, nome: 'Populares', slug: 'populares', verticals: ['slots'], tipo: 'game-list', status: 'active', slots_count: 12 },
        ],
        next_cursor: null,
        prev_cursor: null,
      },
    }).as('getCategoriesSlots');

    cy.get('#vertical').select('slots');
    cy.get('#btnSearch').click();
    cy.wait('@getCategoriesSlots');

    cy.get('#tbody tr').should('have.length', 1);
    cy.get('#tbody').should('contain.text', 'Populares');
  });

  it('UC37: should create a category', () => {
    categoriesPage.visit();
    cy.wait('@getCategories');

    cy.intercept('POST', '/api/v1/categories', {
      statusCode: 201,
      body: { data: { id: 4, nome: 'New Category', slug: 'new-category', tipo: 'game-list', status: 'active' } },
    }).as('createCategory');

    cy.intercept('GET', '/api/v1/categories*', { fixture: 'categories/list.json' }).as('reloadCategories');

    categoriesPage.clickNew();

    cy.get('#editModal').should('be.visible');
    cy.get('#editTitle').should('contain.text', 'Nova');

    categoriesPage.fillNome('New Category');
    categoriesPage.clickSave();

    cy.wait('@createCategory');
    cy.get('.toast').should('contain.text', 'criada');
  });

  it('UC38: should edit a category', () => {
    categoriesPage.visit();
    cy.wait('@getCategories');

    cy.intercept('GET', '/api/v1/categories/1', {
      statusCode: 200,
      fixture: 'categories/single.json',
    }).as('getCategory');

    cy.intercept('PUT', '/api/v1/categories/1', {
      statusCode: 200,
      body: { data: { id: 1, nome: 'Populares Updated', slug: 'populares', status: 'active' } },
    }).as('updateCategory');

    cy.intercept('GET', '/api/v1/categories*', { fixture: 'categories/list.json' }).as('reloadCategories');

    categoriesPage.clickEditOnRow(1);

    cy.get('#editModal').should('be.visible');

    cy.get('#f_nome').clear().type('Populares Updated');
    categoriesPage.clickSave();

    cy.wait('@updateCategory');
    cy.get('.toast').should('contain.text', 'atualizada');
  });

  it('UC39: should manage slots of a category', () => {
    categoriesPage.visit();
    cy.wait('@getCategories');

    cy.intercept('GET', '/api/v1/categories/1', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          nome: 'Populares',
          slug: 'populares',
          slots: [
            { id: 1, title: 'Sweet Bonanza', provider: 'Pragmatic Play', pivot: { position: 1 } },
          ],
        },
      },
    }).as('getCategorySlots');

    cy.intercept('PUT', '/api/v1/categories/1/slots', {
      statusCode: 200,
      body: { message: 'Slots sincronizados.' },
    }).as('syncSlots');

    cy.intercept('GET', '/api/v1/slots*', { fixture: 'slots/list.json' }).as('searchSlots');

    categoriesPage.clickSlotsOnRow(1);

    cy.get('.modal').should('be.visible');
  });

  it('UC40: should sync categories from external API', () => {
    categoriesPage.visit();
    cy.wait('@getCategories');

    cy.intercept('POST', '/api/v1/categories/sync', {
      statusCode: 200,
      body: { message: 'Categorias sincronizadas.' },
    }).as('syncCategories');

    cy.intercept('GET', '/api/v1/categories*', { fixture: 'categories/list.json' }).as('reloadCategories');

    categoriesPage.clickSync();

    cy.wait('@syncCategories');
    cy.get('.toast').should('be.visible');
  });

  it('UC41: should delete a category', () => {
    categoriesPage.visit();
    cy.wait('@getCategories');

    cy.intercept('DELETE', '/api/v1/categories/3', {
      statusCode: 200,
      body: {},
    }).as('deleteCategory');

    cy.intercept('GET', '/api/v1/categories*', { fixture: 'categories/list.json' }).as('reloadCategories');

    cy.on('window:confirm', () => true);

    categoriesPage.clickDeleteOnRow(3);

    cy.wait('@deleteCategory');
    cy.get('.toast').should('contain.text', 'excluída');
  });
});
