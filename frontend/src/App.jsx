import Login from "./pages/Login";
import Register from "./pages/Register";
import { createHashRouter, RouterProvider } from "react-router-dom";
import { RoutesPathName } from "./constants";
import PrivateRoute from "./utils/PrivateRoute";
import AccountPage from "./pages/AccountPage";

const routes = createHashRouter([
  {
    path: RoutesPathName.SIGNUP_PAGE,
    element: <Register />,
  },
  {
    path: RoutesPathName.LOGIN_PAGE,
    element: <Login />,
  },
  {
    path: RoutesPathName.ACCOUNT,
    element: <AccountPage />,
  },
  {
    path: RoutesPathName.DASHBOARD_PAGE,
    element: <PrivateRoute />,
  },
  {
    path: '*',
    element: <Login />,
  }
]);

function App() {
  return <RouterProvider router={routes} />;
}

export default App;
