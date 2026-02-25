import LoginPage from '../../support/page-objects/LoginPage';

describe('Auth - Login', () => {
  const loginPage = new LoginPage();

  beforeEach(() => {
    cy.window().then((win) => {
      win.localStorage.clear();
      win.sessionStorage.clear();
    });
  });

  it('UC1: should login with valid credentials and redirect to dashboard', () => {
    cy.intercept('POST', '/api/v1/auth/login', {
      statusCode: 200,
      fixture: 'auth/login-success.json',
    }).as('loginRequest');

    cy.intercept('GET', '/api/v1/auth/me', {
      fixture: 'auth/me.json',
    }).as('getMe');

    loginPage.visit();
    loginPage.fillEmail('admin@test.com');
    loginPage.fillPassword('password123');
    loginPage.submit();

    cy.wait('@loginRequest').its('request.body').should('deep.include', {
      email: 'admin@test.com',
      password: 'password123',
    });

    cy.url().should('eq', Cypress.config('baseUrl') + '/');
  });

  it('UC2: should show error message on invalid credentials', () => {
    cy.intercept('POST', '/api/v1/auth/login', {
      statusCode: 401,
      body: { error: { message: 'Credenciais inválidas.' } },
    }).as('loginFail');

    loginPage.visit();
    loginPage.fillEmail('wrong@test.com');
    loginPage.fillPassword('wrongpassword');
    loginPage.submit();

    cy.wait('@loginFail');
    loginPage.getAlert().should('be.visible').and('contain.text', 'Credenciais inválidas');
  });

  it('UC3: should show HTML5 validation on empty fields', () => {
    loginPage.visit();
    loginPage.submit();

    // HTML5 required validation prevents submission
    loginPage.getEmailInput().then(($input) => {
      expect($input[0].validity.valueMissing).to.be.true;
    });
  });

  it('UC4: should store token in localStorage when "Manter conectado" is checked', () => {
    cy.intercept('POST', '/api/v1/auth/login', {
      statusCode: 200,
      fixture: 'auth/login-success.json',
    }).as('loginRequest');

    cy.intercept('GET', '/api/v1/auth/me', {
      fixture: 'auth/me.json',
    }).as('getMe');

    loginPage.visit();
    loginPage.checkRemember();
    loginPage.fillEmail('admin@test.com');
    loginPage.fillPassword('password123');
    loginPage.submit();

    cy.wait('@loginRequest');
    cy.window().then((win) => {
      expect(win.localStorage.getItem('betaki_admin_token')).to.eq('fake-cypress-token');
    });
  });

  it('UC5: should redirect to /login when no token is present', () => {
    cy.intercept('GET', '/api/v1/auth/me', {
      statusCode: 401,
      body: { message: 'Unauthenticated.' },
    }).as('getMeUnauth');

    cy.visit('/');

    // The app checks token in localStorage, redirects to login if absent
    cy.url().should('include', '/login');
  });

  it('UC6: should logout, clear token, and redirect to /login', () => {
    cy.intercept('GET', '/api/v1/auth/me', {
      fixture: 'auth/me.json',
    }).as('getMe');

    cy.intercept('POST', '/api/v1/auth/logout', {
      statusCode: 204,
      body: {},
    }).as('logoutRequest');

    // Set token first
    cy.visit('/login');
    cy.window().then((win) => {
      win.localStorage.setItem('betaki_admin_token', 'fake-cypress-token');
      win.localStorage.setItem('betaki_admin_expires_at', '2099-12-31');
    });

    cy.visit('/');
    cy.wait('@getMe');

    cy.get('#logoutBtn').click();

    cy.wait('@logoutRequest');
    cy.url().should('include', '/login');

    cy.window().then((win) => {
      expect(win.localStorage.getItem('betaki_admin_token')).to.be.null;
    });
  });
});
