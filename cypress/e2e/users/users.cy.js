import UsersPage from '../../support/page-objects/UsersPage';

describe('Users', () => {
  const usersPage = new UsersPage();

  beforeEach(() => {
    cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
    cy.intercept('GET', '/api/v1/users*', { fixture: 'users/list.json' }).as('getUsers');
    cy.intercept('GET', '/api/v1/permissions', { fixture: 'permissions/list.json' }).as('getPermissions');
  });

  it('UC83: should list users', () => {
    usersPage.visit();
    cy.wait('@getUsers');

    cy.get('#tbody tr').should('have.length', 3);
    cy.get('#tbody').should('contain.text', 'Admin Test');
    cy.get('#tbody').should('contain.text', 'Editor User');
    cy.get('#tbody').should('contain.text', 'Suspended User');
  });

  it('UC84: should search by name/email', () => {
    usersPage.visit();
    cy.wait('@getUsers');

    cy.intercept('GET', '/api/v1/users*q=admin*', {
      body: {
        data: [{ id: 1, name: 'Admin Test', email: 'admin@test.com', status: 'active', roles: ['admin'] }],
        next_cursor: null,
        prev_cursor: null,
      },
    }).as('searchUsers');

    usersPage.searchByQuery('admin');
    cy.wait('@searchUsers');

    cy.get('#tbody tr').should('have.length', 1);
    cy.get('#tbody').should('contain.text', 'Admin Test');
  });

  it('UC85: should create a user with roles', () => {
    usersPage.visit();
    cy.wait('@getUsers');

    cy.intercept('POST', '/api/v1/users', {
      statusCode: 201,
      body: { data: { id: 4, name: 'New User', email: 'new@test.com', status: 'active', roles: ['editor'] } },
    }).as('createUser');

    cy.intercept('GET', '/api/v1/users*', { fixture: 'users/list.json' }).as('reloadUsers');

    usersPage.clickNew();

    cy.get('#editModal').should('be.visible');

    usersPage.fillName('New User');
    usersPage.fillEmail('new@test.com');
    usersPage.fillPassword('securePassword123');
    usersPage.selectStatus('active');
    usersPage.clickSave();

    cy.wait('@createUser');
    cy.get('.toast').should('contain.text', 'criado');
  });

  it('UC86: should edit a user', () => {
    usersPage.visit();
    cy.wait('@getUsers');

    cy.intercept('GET', '/api/v1/users/2', {
      statusCode: 200,
      body: { data: { id: 2, name: 'Editor User', email: 'editor@test.com', status: 'active', roles: ['editor'] } },
    }).as('getUser');

    cy.intercept('PUT', '/api/v1/users/2', {
      statusCode: 200,
      body: { data: { id: 2, name: 'Editor Updated', email: 'editor@test.com', status: 'active', roles: ['editor'] } },
    }).as('updateUser');

    cy.intercept('GET', '/api/v1/users*', { fixture: 'users/list.json' }).as('reloadUsers');

    usersPage.clickEditOnRow(2);

    cy.get('#editModal').should('be.visible');

    cy.get('#f_name').clear().type('Editor Updated');
    usersPage.clickSave();

    cy.wait('@updateUser');
    cy.get('.toast').should('contain.text', 'atualizado');
  });

  it('UC87: should delete a user', () => {
    usersPage.visit();
    cy.wait('@getUsers');

    cy.intercept('DELETE', '/api/v1/users/3', {
      statusCode: 200,
      body: {},
    }).as('deleteUser');

    cy.intercept('GET', '/api/v1/users*', { fixture: 'users/list.json' }).as('reloadUsers');

    cy.on('window:confirm', () => true);

    usersPage.clickDeleteOnRow(3);

    cy.wait('@deleteUser');
    cy.get('.toast').should('contain.text', 'excluído');
  });
});
