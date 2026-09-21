import {BrowserRouter, Routes, Route} from "react-router-dom";
import AppLayout from "./components/layout/AppLayout";
import EventDetailsPage from "./pages/EventDetailPage";
import HomePage from "./pages/HomePage";
import LoginPage from "./pages/LoginPage";
import MyApplicationsPage from "./pages/MyApplicationsPage";
import RegisterPage from "./pages/RegisterPage";
import "./App.css";

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<AppLayout/>}>
          <Route path="/" element={<HomePage/>} />
          <Route path="/events/:eventId" element={<EventDetailsPage/>} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route path="/my-applications" element={<MyApplicationsPage />} />
        </Route>
      </Routes>
    </BrowserRouter>
  );
}