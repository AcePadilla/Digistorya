<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIGIstorya</title>
    <link rel="icon" href="../../images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="https://kit.fontawesome.com/aed89df169.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Island+Moments&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  body{
    box-sizing: border-box;
  }
  .progress-bar {
    width: 0;
    transition: width 1s ease-in-out; 
  }
  * { font-family: 'Montserrat', sans-serif; padding: 0; margin: 0 auto}
  .input-group:focus-within label {
    transform: translateY(-1.5rem);
    font-size: 0.75rem;
  }
  .answer-button {
    transition: background-color 0.3s ease, border-color 0.3s ease;
  }
  .answer-button:hover {
    background-color:#551a25; 
    border-color:#551a25;
    color: white;
  }
</style>
<body class="bg-[#f0e8e1] min-h-screen font-sans">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 pb-6">
        <!-- Header Section -->
            <div  class="bg-[#551a25] py-10 shadow-lg">
              <div class="max-w-7xl mx-auto px-6 flex flex-col lg:flex-row items-center justify-center gap-6">
                <!-- Logo -->
                <img src="../../images/logo.png" alt="GJC Logo" class="h-24 sm:h-32 md:h-40 lg:h-48 w-auto object-contain  transition-transform duration-300 hover:scale-105">

                <!-- Title -->
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white text-center lg:text-left leading-snug tracking-tight">
                  Students’ Learning Styles and Teachers' Strategies in Teaching Social Studies
                </h1>
              </div>
            </div>
            <!-- Introduction Section -->
            <div class="bg-white py-8 px-6 sm:px-8 lg:px-10 rounded-2xl shadow-xl mx-auto mb-8 max-w-5xl transition-shadow duration-300 hover:shadow-2xl">
              <h2 class="text-2xl font-semibold text-[#551a25] mb-4 text-center sm:text-left">
                Introduction
              </h2>
              <p class="text-lg text-gray-800 text-justify leading-relaxed tracking-wide">
                <strong>DIRECTIONS:</strong> For the students, answer the following statements from the <em>Felder-Soloman Index of Learning Styles</em>. Please choose only one answer for each question. If both “A” and “B” seem to apply to you, select the one that applies more frequently.
              </p>
            </div>
        <!-- Quiz Section -->
        <div id="quiz" class="space-y-6 p-5 mb-5 bg-white border-t-8 border-[#551a25]"></div>
        <!-- Comments Section (hidden initially) -->
        <div id="commentSection" class="hidden mt-8">
            <label for="comments" class="block text-lg font-medium text-gray-700 mb-2">Any comments or suggestions?</label>
            <textarea id="comments" rows="4" class="w-full border border-gray-300 rounded-xl p-3 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
        </div>
        <div id="result" class="hidden opacity-0 transition-opacity duration-1000">
            <div class="bg-gray-200 h-6 rounded-full overflow-hidden">
                <div class="progress-bar bg-blue-500 h-full rounded-full" style="width: 0%" id="bar-A1"></div>
            </div>
        </div>
        <div class="flex justify-between mt-6">
        <!-- Previous Button -->
          <button id="prevBtn"
                  onclick="prevPage()"
                  style="background-color: #551a25; color: white;"
                  class="px-6 py-2 font-medium rounded-md shadow-sm transition duration-200 hover:brightness-110 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#551a25] hidden">
            ← Previous
          </button>
          <!-- Next Button -->
          <button id="nextBtn"
                  onclick="nextPage()"
                  style="background-color: #551a25; color: white;"
                  class="px-6 py-2 font-semibold rounded-md shadow-sm transition duration-200 hover:brightness-110 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#551a25]">
            Next →
          </button>
        </div>
    </div>
  <script>
    const questions = [
  {
    question: "I understand something better after… <br><span class='italic text-gray-500'>(Naiintindihan ko nang mas mabuti ang isang bagay kapag…)</span>",
    a: { text: "A. try it out. (sinusubukan ko ito.)", trait: "A1" },
    b: { text: "B. think it through. (iniisip ko ito ng mabuti.)", trait: "B1" }
  },
  {
    question: "When I am learning something new, it helps me to… <br><span class='italic text-gray-500'>(Kapag may natutunan akong bago, nakakatulong ito sa akin na…)</span>",
    a: { text: "A. talk about it. (pag-usapan ito.)", trait: "A1" },
    b: { text: "B. think about it. (pag-isipan ito.)", trait: "B1" }
  },
  {
    question: "In a study group working on difficult material, I am more likely to… <br><span class='italic text-gray-500'>(Kapag nasa isang pangkatang gawain, ako ay…)</span>",
    a: { text: "A. jump in and contribute ideas. (nakikilahok at nagbibigay ng mga ideya.)", trait: "A1" },
    b: { text: "B. sit back and listen. (maupo at makinig.)", trait: "B1" }
  },
  {
    question: "In classes I have taken… <br><span class='italic text-gray-500'>(Sa mga klaseng aking kinabibilangan…)</span>",
    a: { text: "A. I have usually gotten to know many of the students. (karaniwang nakikilala kong mabuti ang aking mga kamag-aral.)", trait: "A1" },
    b: { text: "B. I have rarely gotten to know many of the students. (bihirang nakilala ko ang maraming kong kamag-aral.)", trait: "B1" }
  },
  {
    question: "When I start a homework problem, I am more likely to… <br><span class='italic text-gray-500'>(Kapag gumagawa ako ng takdang aralin, ako ay…)</span>",
    a: { text: "A. start working on the solution immediately. (sinimulan ko agad hanapan ng solusyon.)", trait: "A1" },
    b: { text: "B. try to fully understand the problem first. (iniintindi ko nang mabuti ang problema.)", trait: "B1" }
  },
  {
    question: "I prefer to study… <br><span class='italic text-gray-500'>(Mas gusto kong mag-aral…)</span>",
    a: { text: "A. in a study group. (sa isang pangkatang gawain.)", trait: "A1" },
    b: { text: "B. alone. (mag-isa.)", trait: "B1" }
  },
  {
    question: "I would rather first… <br><span class='italic text-gray-500'>(Mas gusto ko muna…)</span>",
    a: { text: "A. try things out. (subukan ang mga bagay.)", trait: "A1" },
    b: { text: "B. think about how I'm going to do it. (pag-isipan kung paano ko ito gagawin.)", trait: "B1" }
  },
  {
    question: "I would rather be considered… <br><span class='italic text-gray-500'>(Mas nais kong makilala bilang…)</span>",
    a: { text: "A. realistic. (makatotohanan.)", trait: "A2" },
    b: { text: "B. innovative. (makabago.)", trait: "B2" }
  },
  {
    question: "If I were a teacher, I would rather teach a course… <br><span class='italic text-gray-500'>(Kung ako ay isang guro, mas pipiliin kong magturo ng aralin na…)</span>",
    a: { text: "A. that deals with facts and real-life situations. (tumatalakay sa mga katotohanan at mga sitwasyong tunay sa buhay.)", trait: "A2" },
    b: { text: "B. that deals with ideas and theories. (tumatalakay sa mga ideya at teorya.)", trait: "B2" }
  },
  {
    question: "I find it easier… <br><span class='italic text-gray-500'>(Mas madali para sa akin…)</span>",
    a: { text: "A. to learn facts. (na aralin ang mga bagay na batay sa katotohanan.)", trait: "A2" },
    b: { text: "B. to learn concepts. (na aralin ang mga konsepto lamang.)", trait: "B2" }
  },
  {
    question: "In reading nonfiction, I prefer… <br><span class='italic text-gray-500'>(Sa pagbabasa ng makatotohanang babasahin…)</span>",
    a: { text: "A. something that teaches me new facts or tells me how to do something. (ang nagtuturo ng bagong kaalaman o nagsasabi kung paano gawin.)", trait: "A2" },
    b: { text: "B. something that gives me new ideas to think about. (ang nagbibigay sa akin ng mga bagong ideya upang pag-isipan.)", trait: "B2" }
  },
  {
    question: "I prefer the idea of… <br><span class='italic text-gray-500'>(Mas gusto ko ang ideya ng…)</span>",
    a: { text: "A. certainty. (katiyakan.)", trait: "A2" },
    b: { text: "B. theory. (teorya.)", trait: "B2" }
  },
  {
    question: "I am more likely to be considered… <br><span class='italic text-gray-500'>(Ako ay itinuturing na…)</span>",
    a: { text: "A. careful about the details of my work. (maingat sa mga detalye.)", trait: "A2" },
    b: { text: "B. creative about how to do my work. (malikhain sa paggawa ng trabaho.)", trait: "B2" }
  },
  {
    question: "When I am reading for enjoyment, I like writers to… <br><span class='italic text-gray-500'>(Kapag ako ay nagbabasa para sa kasiyahan…)</span>",
    a: { text: "A. clearly say what they mean. (malinaw na sinasabi ang ibig nilang iparating.)", trait: "A2" },
    b: { text: "B. say things in creative, interesting ways. (naglalahad ng paraang malikhain at kawili-wili.)", trait: "B2" }
  },
  {
    question: "When I think about what I did yesterday, I am most likely to get… <br><span class='italic text-gray-500'>(Kapag iniisip ko ang ginawa ko kahapon…)</span>",
    a: { text: "A. a picture. (larawan.)", trait: "A3" },
    b: { text: "B. words. (mga salita.)", trait: "B3" }
  },
  {
    question: "I prefer to get new information in… <br><span class='italic text-gray-500'>(Mas gusto kong matuto sa pamamagitan ng…)</span>",
    a: { text: "A. pictures, diagrams, graphs, or maps. (mga larawan, diagram, graph, o mapa.)", trait: "A3" },
    b: { text: "B. written directions or verbal information. (nakasulat o sinasabi.)", trait: "B3" }
  },
  {
    question: "In a book with lots of pictures and charts, I am likely to… <br><span class='italic text-gray-500'>(Sa librong may maraming larawan…)</span>",
    a: { text: "A. look over the pictures and charts carefully. (pinagmasdan ko ng mabuti ang mga larawan.)", trait: "A3" },
    b: { text: "B. focus on the written text. (tumutok sa nakasulat na teksto.)", trait: "B3" }
  },
  {
    question: "I like teachers who… <br><span class='italic text-gray-500'>(Mas gusto ko ang mga guro na…)</span>",
    a: { text: "A. put a lot of diagrams on the board. (gumagamit ng maraming diagram.)", trait: "A3" },
    b: { text: "B. spend a lot of time explaining. (nagpapaliwanag ng mas detalyado.)", trait: "B3" }
  },
  {
    question: "I remember best… <br><span class='italic text-gray-500'>(Mas natatandaan ko ang…)</span>",
    a: { text: "A. what I see. (ang mga bagay na nakikita ko.)", trait: "A3" },
    b: { text: "B. what I hear. (ang mga bagay na naririnig ko.)", trait: "B3" }
  },
  {
    question: "When I get directions to a new place, I prefer… <br><span class='italic text-gray-500'>(Mas gusto kong…)</span>",
    a: { text: "A. a map. (ang mapa.)", trait: "A3" },
    b: { text: "B. written directions. (nakasulat na direksyon.)", trait: "B3" }
  },
  {
    question: "When I see a diagram or sketch in class, I am most likely to remember… <br><span class='italic text-gray-500'>(Mas natatandaan ko…)</span>",
    a: { text: "A. the picture. (ang larawan.)", trait: "A3" },
    b: { text: "B. what the instructor said about it. (ang sinabi ng guro tungkol dito.)", trait: "B3" }
  },
  {
    question: "I tend to… <br><span class='italic text-gray-500'>(May ugali ako na…)</span>",
    a: { text: "A. understand details but not the whole structure. (maunawaan ang detalye pero malabo ang kabuuan.)", trait: "A4" },
    b: { text: "B. understand the whole structure but not the details. (maunawaan ang kabuuan pero hindi ang detalye.)", trait: "B4" }
  },
  {
    question: "Once I understand… <br><span class='italic text-gray-500'>(Kapag naiintindihan ko na…)</span>",
    a: { text: "A. all the parts, I understand the whole thing. (lahat ng bahagi, naiintindihan ko ang buo.)", trait: "A4" },
    b: { text: "B. the whole thing, I see how the parts fit. (ang kabuuan, nakikita ko kung paano konektado ang bahagi.)", trait: "B4" }
  },
  {
    question: "When I solve Math problems… <br><span class='italic text-gray-500'>(Kapag nagso-solve ako ng Math problems…)</span>",
    a: { text: "A. I usually work one step at a time. (sunod-sunod ang hakbang.)", trait: "A4" },
    b: { text: "B. I often just see the solutions. (nakikita agad ang sagot pero hirap sa steps.)", trait: "B4" }
  },
  {
    question: "When I'm analyzing a story… <br><span class='italic text-gray-500'>(Kapag nag-a-analyze ako ng kwento…)</span>",
    a: { text: "A. I think of the incidents and try to find the themes. (pinagsasama-sama ko ang mga pangyayari para makita ang tema.)", trait: "A4" },
    b: { text: "B. I know the themes first then find examples. (alam ko agad ang tema, tapos hinahanap ang patunay.)", trait: "B4" }
  },
  {
    question: "It is more important to me that an instructor… <br><span class='italic text-gray-500'>(Mas mahalaga sa akin ang guro na…)</span>",
    a: { text: "A. lay out the material in clear sequential steps. (magpaliwanag nang sunod-sunod.)", trait: "A4" },
    b: { text: "B. give an overview and relate to other subjects. (ipakita ang kabuuan at koneksyon sa ibang paksa.)", trait: "B4" }
  },
  {
    question: "I learn… <br><span class='italic text-gray-500'>(Natututo ako…)</span>",
    a: { text: "A. at a fairly regular pace. (unti-unti basta pag-aaralan ko.)", trait: "A4" },
    b: { text: "B. in fits and starts. (nalilito muna pero biglang naiintindihan.)", trait: "B4" }
  },
  {
    question: "When considering information, I tend to… <br><span class='italic text-gray-500'>(Kapag tumitingin ako ng impormasyon…)</span>",
    a: { text: "A. focus on small details. (tinutok ang detalye.)", trait: "A4" },
    b: { text: "B. focus on overall concepts. (tinutok ang kabuuan ng konsepto.)", trait: "B4" }
  }
];
const questionsPerPage = 7; // editable to how many page 
        let currentPage = 0;
        const answers = Array(questions.length).fill(null);
        const scores = { A1: 0, B1: 0, A2: 0, B2: 0, A3: 0, B3: 0, A4: 0, B4: 0 };

        const quizEl = document.getElementById('quiz');
        const resultEl = document.getElementById('result');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        function renderQuestions() {
            const start = currentPage * questionsPerPage;
            const end = start + questionsPerPage;
            const currentQuestions = questions.slice(start, end);

            quizEl.innerHTML = currentQuestions.map((q, index) => {
                const qIndex = start + index;
                const selected = answers[qIndex];
                return `
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">${qIndex + 1}. ${q.question}</h2>
                        <div class="grid grid-cols-1 gap-4 mt-4">
                            <button 
                                onclick="selectAnswer(${qIndex}, '${q.a.trait}')" 
                                style="${selected === q.a.trait ? 'background-color: #551a25; color: white;' : ''}"
                                class="answer-button text-gray-600 border-2 rounded-lg px-6 py-3 text-left font-medium transition-colors duration-200 hover:bg-[#551a25] focus:outline-none focus:ring-2 focus:ring-[#551a25] ${selected === q.a.trait ? 'selected' : ''}">
                                ${q.a.text}
                            </button>

                            <button 
                                onclick="selectAnswer(${qIndex}, '${q.b.trait}')" 
                                style="${selected === q.b.trait ? 'background-color: #551a25; color: white;' : ''}"
                                class="answer-button text-gray-600 border-2 rounded-lg px-6 py-3 text-left font-medium transition-colors duration-200 hover:bg-[#551a25] focus:outline-none focus:ring-2 focus:ring-[#551a25] ${selected === q.b.trait ? 'selected' : ''}">
                                ${q.b.text}
                            </button>
                        </div>
                    </div>
                `;
            }).join('');

            if (currentPage * questionsPerPage >= questions.length - questionsPerPage) {
                document.getElementById('commentSection').classList.remove('hidden');
                nextBtn.textContent = 'Finish';
            } else {
                document.getElementById('commentSection').classList.add('hidden');
                nextBtn.textContent = 'Next';
            }

            prevBtn.classList.toggle('hidden', currentPage === 0);
        }

        function selectAnswer(qIndex, trait) {
            answers[qIndex] = trait;
            renderQuestions();
        }

        function nextPage() {
            const isLastPage = (currentPage + 1) * questionsPerPage >= questions.length;
            if (isLastPage) {
                if (answers.includes(null)) {
                    Swal.fire("Incomplete", "Please answer all questions before submitting.", "warning");
                    return;
                }

                document.getElementById('commentSection').classList.remove('hidden');
                nextBtn.textContent = 'Finish';
                nextBtn.onclick = submitAll;
            } else {
                currentPage++;
                renderQuestions();
            }
        }

        function prevPage() {
            if (currentPage > 0) {
                currentPage--;
                renderQuestions();
            }
        }

        function submitAll() {
            const comments = document.getElementById('comments').value.trim();

            answers.forEach(trait => scores[trait]++);

            quizEl.classList.add('hidden');
            prevBtn.classList.add('hidden');
            nextBtn.classList.add('hidden');
            document.getElementById('commentSection').classList.add('hidden');
            resultEl.classList.remove('hidden');


            resultEl.classList.remove('hidden');
            setTimeout(() => {
              resultEl.classList.add('opacity-100');
            }, 50);

 fetch('submit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          active_reflective: `${scores['A1']}|${scores['B1']}`,
          sensing_intuitive: `${scores['A2']}|${scores['B2']}`,
          visual_verbal: `${scores['A3']}|${scores['B3']}`,
          sequential_global: `${scores['A4']}|${scores['B4']}`,
          comments: comments
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          Swal.fire("Success!", "Your quiz and feedback have been saved!", "success");
        } else {
          Swal.fire("Error!", data.message || "Could not save your results.", "error");
        }
      })
      .catch(() => {
        Swal.fire("Connection Error", "Could not connect to the server.", "error");
      });

      const resultHTML = (title, a, b, labelA, labelB) => {
      const aVal = scores[a];
      const bVal = scores[b];
      const total = aVal + bVal;
      const percentageA = (aVal / total) * 100;
      const percentageB = (bVal / total) * 100;
      let dominant = 'Balanced';
      let description = '';

      if (aVal > bVal) {
        dominant = labelA;
        // Description for dominant A
        if (labelA === 'Active') description = 'Active learners learn by direct interaction with the material; prefer group communication.';
        else if (labelA === 'Sensing') description = 'Sensing learners are detail-oriented and practical with a preference for concrete facts and real-world application.';
        else if (labelA === 'Visual') description = 'Visual learners are better able to remember images they have seen (charts, graphs, pictures).';
        else if (labelA === 'Sequential') description = 'Sequential learners prefer learning linearly, with logical steps.';
      } else if (bVal > aVal) {
        dominant = labelB;
        // Description for dominant B
        if (labelB === 'Reflective') description = "Reflective learners like to think about the material; prefer individual or very small group communication.";
        else if (labelB === 'Intuitive') description = 'Intuitive learners have a creative disposition and are drawn to the theoretical and abstract.';
        else if (labelB === 'Verbal') description = 'Verbal learners are better able to remember written or spoken words.';
        else if (labelB === 'Global') description = 'Global learners prefer a holistic approach and seem to learn almost randomly by fitting pieces together into a big picture.';
      } else {
        // Balanced description
        description = 'You show a balanced preference between both learning styles in this category.';
      }

  return `
  <div class="bg-white border border-[#f0e8e1] rounded-2xl p-6 shadow-xl hover:shadow-2xl transition-all mb-6">
  <h3 class="text-2xl font-semibold text-[#551a25] mb-3">${title}</h3>
  <p class=" mb-4 text-lg">You are more <span class="font-bold text-[#551a25]">${dominant}</span></p>
  <p class=" italic mb-4">${description}</p>
  
  <div class="relative h-8 bg-[#f0e8e1] rounded-full overflow-hidden shadow-inner">
    <div class="absolute left-1/2 transform -translate-x-1/2 w-1 h-full bg-white z-10"></div>
    <div class="absolute left-1/2 transform -translate-x-full h-full ${percentageA > percentageB ? 'bg-[#551a25]' : 'bg-gray-400'} progress-bar" 
         data-final-width="${percentageA / 2}%">
    </div>
    <div class="absolute left-1/2 h-full ${percentageA > percentageB ? 'bg-gray-400' : 'bg-[#551a25]'} progress-bar" 
         data-final-width="${percentageB / 2}%">
    </div>
  </div>
  
  <div class="flex justify-between text-sm font-medium mt-2">
    <span>${labelA} (${aVal})</span>
    <span>${labelB} (${bVal})</span>
  </div>
   

</div>

  `;
};
      resultEl.innerHTML =
        resultHTML('Active vs Reflective', 'A1', 'B1', 'Active', 'Reflective') +
        resultHTML('Sensing vs Intuitive', 'A2', 'B2', 'Sensing', 'Intuitive') +
        resultHTML('Visual vs Verbal', 'A3', 'B3', 'Visual', 'Verbal') +
        resultHTML('Sequential vs Global', 'A4', 'B4', 'Sequential', 'Global') +
        `
        <div class="text-center mt-6">
          <a href="home.php">
            <button class="bg-[#551a25] text-white px-6 py-3 rounded-lg shadow hover:bg-[#3e111b] transition">
              <i class="fa-solid fa-house"></i> Home
            </button>
          </a>
        </div>
        `;

      setTimeout(() => {
        document.querySelectorAll('.progress-bar').forEach(bar => {
          const finalWidth = bar.getAttribute('data-final-width');
          bar.style.width = finalWidth;
        });
      }, 100); 
    }
    renderQuestions();
    </script>
</body>
</html>