import Header from '../components/Header';

export default function MainLayout({ children }) {
  return (
    <>
      <Header />
      <main className="page-container">{children}</main>
    </>
  );
}
