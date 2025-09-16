const firebaseConfig = {
  apiKey: "AIzaSyATRaSl5a2du8WJ1oUTUK1V-VzRExZV-8Q",
  authDomain: "eaaps-45d6a.firebaseapp.com",
  databaseURL: "https://eaaps-45d6a-default-rtdb.asia-southeast1.firebasedatabase.app",
  projectId: "eaaps-45d6a",
  storageBucket: "eaaps-45d6a.firebasestorage.app",
  messagingSenderId: "582520872055",
  appId: "1:582520872055:web:12ccfe9945305c211cfa72"
};

firebase.initializeApp(firebaseConfig);

window.database = firebase.database();
