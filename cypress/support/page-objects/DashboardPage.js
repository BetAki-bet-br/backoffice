import BasePage from './BasePage';

export default class DashboardPage extends BasePage {
  constructor() {
    super('/');
  }

  getKpiCards() {
    return cy.get('.card');
  }

  getPageTitle() {
    return cy.get('.h5').first();
  }

  getWelcomeSection() {
    return cy.get('.content-main');
  }
}
